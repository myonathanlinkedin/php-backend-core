# Bloom Filter with MurmurHash3 Hash Functions

An in-memory reference implementation of **Bloom Filter with MurmurHash3 Hash Functions** in **PHP**, adhering to standard library idioms, clean data structures, and assertion test suites.

---

## 🏛️ Architecture & Design Decisions

This module organizes `Bloom Filter with MurmurHash3 Hash Functions` into an isolated, self-contained unit:
* **Domain Focus**: `Distributed Consensus & State Machine`
* **Primary Primitives**: `Append-Only State Log & Version Matrix`
* **Memory Strategy**: Contiguous memory layouts and standard collections are favored for straightforward iteration and access.
* **Correctness Model**: State consistency is verified after mutations through assertion test coverage.

### Asymptotic Complexity

| Metric | Bound | Characteristics |
| :--- | :---: | :--- |
| **Best Case Time** | `O(1)` | Optimized fast-path execution |
| **Average / Worst Time** | `O(log N) or O(1)` | Deterministic upper bound for generalized workloads |
| **Space Complexity** | `O(N) state log` | Strict bounds without unconstrained heap growth |

---

## 🧪 Verification Suite

The accompanying `core.php` driver executes self-contained verification tests:
1. **Nominal Flow**: Validates baseline correctness under typical real-world inputs.
2. **Boundary Conditions**: Exercises extreme edge cases (empty inputs, singletons, capacity limits).
3. **Invariant Preservation**: Validates internal state consistency throughout mutation lifecycles.

### Running Locally

```bash
php core.php
```

---

*Source code released under the MIT License • [@myonathanlinkedin](https://github.com/myonathanlinkedin)*