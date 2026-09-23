# Repository Implementation Guidelines

This document outlines the guidelines for implementing repositories in this Laravel application, focusing on consistency, testability, and maintainability.

## 1. Core Principles

*   **Dependency Injection:** Repositories should primarily use constructor injection for their model dependencies.
*   **Abstraction:** Repositories should implement an interface to define their contract, promoting loose coupling.
*   **Single Responsibility:** Each repository should be responsible for interacting with a single Eloquent model.

## 2. Guidelines

1.  **All repositories MUST extend `BaseRepository`**
    *   The `BaseRepository` provides common CRUD operations and a consistent interface.

2.  **All repositories MUST implement the `public model()` method**
    *   This method should return the fully qualified class name of the Eloquent model the repository is responsible for. This is used by the `makeModel()` method in `BaseRepository` for creating new instances.

3.  **Method visibility MUST match or be less restrictive than parent**
    *   When overriding methods from `BaseRepository`, ensure the visibility (public, protected) is not made more restrictive.

4.  **Namespace MUST be `App\Repositories`**
    *   Ensure all repository classes are within the `App\Repositories` namespace.

5.  **Constructor Injection for Models**
    *   Child repositories should accept their specific model instance in their constructor and pass it to the parent constructor using `parent::__construct($model);`.

## 3. Repository Template

Use the following template when creating new repositories:

```php
<?php

namespace App\Repositories;

use App\Models\{ModelName};
use App\Repositories\Interfaces\{ModelName}RepositoryInterface;
use Illuminate\Database\Eloquent\Model; // Ensure this is imported

/**
 * Repository for {ModelName} model
 * 
 * @package App\Repositories
 */
class {ModelName}Repository extends BaseRepository implements {ModelName}RepositoryInterface
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model(): string
    {
        return {ModelName}::class;
    }

    /**
     * Constructor
     *
     * @param {ModelName} $model
     */
    public function __construct({ModelName} $model)
    {
        parent::__construct($model);
    }
    
    // Add custom repository methods below
}
```

## 4. Common Issues and Solutions

| Issue | Solution |
|---|---|
| Method visibility mismatch | Ensure child method has same or less restrictive visibility |
| Abstract method not implemented | Implement all abstract methods from parent |
| Namespace typo | Use correct namespace `App\Repositories` |
| Model class not found | Ensure proper use statement and model exists |