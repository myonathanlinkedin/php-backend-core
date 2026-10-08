<?php
declare(strict_types=1);

namespace Nats;

require_once __DIR__ . '/types.php';

/**
 * Core in‑memory NATS server implementation.
 */
final class Server
{
    /** @var Subscription[] */
    private array $subscriptions = [];

    /** @var int */
    private int $nextId = 1;

    /**
     * Subscribe to a subject pattern.
     *
     * @param string   $subject Subject pattern (supports * and > wildcards)
     * @param callable(Message): void $handler Callback invoked for each matching message
     *
     * @return int Subscription identifier
     */
    public function subscribe(string $subject, callable $handler): int
    {
        $id = $this->nextId++;
        $this->subscriptions[$id] = new Subscription($id, $subject, $handler);
        return $id;
    }

    /**
     * Unsubscribe a previously created subscription.
     */
    public function unsubscribe(int $id): void
    {
        unset($this->subscriptions[$id]);
    }

    /**
     * Publish a message to a subject.
     */
    public function publish(string $subject, string $payload): void
    {
        $msg = new Message($subject, $payload);
        foreach ($this->subscriptions as $sub) {
            if (self::matchSubject($sub->getSubject(), $subject)) {
                $handler = $sub->getHandler();
                $handler($msg);
            }
        }
    }

    /**
     * Return current subscription count (useful for tests).
     */
    public function getSubscriptionCount(): int
    {
        return count($this->subscriptions);
    }

    /**
     * Determine whether a subscription pattern matches a concrete subject.
     *
     * NATS subject rules:
     * - Tokens are dot‑separated.
     * - '*' matches any single token.
     * - '>' matches any remaining tokens and must be the last token in the pattern.
     */
    public static function matchSubject(string $pattern, string $subject): bool
    {
        $pTokens = $pattern === '' ? [] : explode('.', $pattern);
        $sTokens = $subject === '' ? [] : explode('.', $subject);

        $pCount = count($pTokens);
        $sCount = count($sTokens);

        for ($i = 0, $j = 0; $i < $pCount; $i++, $j++) {
            $p = $pTokens[$i];

            if ($p === '>') {
                // '>' consumes the rest of the subject, always matches.
                return true;
            }

            if ($j >= $sCount) {
                // Subject ran out of tokens before pattern.
                return false;
            }

            $s = $sTokens[$j];

            if ($p !== '*' && $p !== $s) {
                return false;
            }
        }

        // If pattern exhausted, subject must also be exhausted.
        return $j === $sCount;
    }
}
