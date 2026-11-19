<?php

class NotificationEngine
{
    private SplQueue $queue;
    private SplQueue $retryQueue;
    private SenderInterface $sender;
    private RetryPolicy $policy;
    /** @var array<string, array> */
    private array $sentLog = [];

    public function __construct(SenderInterface $sender, RetryPolicy $policy)
    {
        $this->queue = new SplQueue();
        $this->retryQueue = new SplQueue();
        $this->sender = $sender;
        $this->policy = $policy;
    }

    public function enqueue(Notification $notification): void
    {
        $this->queue->enqueue($notification);
    }

    public function process(int $currentTime): void
    {
        // Move due retries to main queue
        while (!$this->retryQueue->isEmpty()) {
            $front = $this->retryQueue->bottom();
            if ($front->nextAttemptTime > $currentTime) {
                break;
            }
            $this->retryQueue->dequeue();
            $this->queue->enqueue($front->notification);
        }

        // Process main queue
        while (!$this->queue->isEmpty()) {
            /** @var Notification $notification */
            $notification = $this->queue->dequeue();
            $sent = $this->sender->send($notification, $notification->recipient);
            if ($sent) {
                $this->logSent($notification);
                continue;
            }

            $notification->attempts += 1;
            if ($notification->attempts < $notification->maxAttempts) {
                $delay = $this->policy->getDelay($notification->attempts);
                $retryItem = new QueueItem($notification, $currentTime + $delay);
                $this->retryQueue->enqueue($retryItem);
            } else {
                // Max attempts reached, log failure
                $this->logFailed($notification);
            }
        }
    }

    private function logSent(Notification $notification): void
    {
        $key = $notification->id . '|' . $notification->recipient->id;
        $this->sentLog[$key] = [
            'notificationId' => $notification->id,
            'recipientId' => $notification->recipient->id,
            'attempts' => $notification->attempts + 1,
            'status' => 'sent',
        ];
    }

    private function logFailed(Notification $notification): void
    {
        $key = $notification->id . '|' . $notification->recipient->id;
        $this->sentLog[$key] = [
            'notificationId' => $notification->id,
            'recipientId' => $notification->recipient->id,
            'attempts' => $notification->attempts,
            'status' => 'failed',
        ];
    }

    /** @return array<string, array> */
    public function getSentLog(): array
    {
        return $this->sentLog;
    }
}
