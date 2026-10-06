# Chronicle Queue - Micro second messaging that stores everything to disk

High-performance **Chronicle Queue - Micro second messaging that stores everything to disk** primitive implemented in idiomatic **PHP**. Built from scratch using standard library constructs with zero external dependencies.

### Core Highlights
* **Language & Standard**: Modern `PHP` standard library conventions.
* **Architecture Pattern**: Designed for `Low-Latency Systems & Memory Layout` using `Contiguous Memory Buffer & Ring Pointers`.
* **Runtime Overhead**: Buffer boundaries are strictly verified to prevent out-of-bounds access and memory leak hazards.
* **Concurrency & Safety**: State consistency is verified after every mutation through formal invariant validation.

---

### Complexity Analysis

| Dimension | Bound |
| :--- | :--- |
| **Time (Best Case)** | `$O(1)$` |
| **Time (Worst Case)** | `$O(1) amortized$` |
| **Auxiliary Space** | `$O(N) bounded$` |

---

### Test Suite Execution

Self-contained verification drivers are embedded directly in `main.php` to validate happy paths, boundary inputs, and invariant preservation.

```bash
php main.php
```

---

<sub>Crafted with modern PHP standards • Maintained by [@myonathanlinkedin](https://github.com/myonathanlinkedin)</sub>