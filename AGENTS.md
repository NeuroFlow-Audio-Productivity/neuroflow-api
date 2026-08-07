# Backend

This directory contains the entire NeuroFlow backend API.

The API should be developed with readability, scalability, and maintainability as top priorities. All code must follow the project's established architecture and conventions, avoiding unnecessary complexity while maintaining consistency across modules.

## Goals

* Write clean, readable, and maintainable code.
* Build a scalable and loosely coupled architecture.
* Make the application easy to maintain and evolve.
* Centralize business logic in a dedicated layer.
* Avoid code duplication.
* Maximize code reuse whenever possible.

---

# Architecture

The project follows the **Repository + Service Pattern** using the Laravel framework.

## Layers

### Controllers

Controllers must remain as thin as possible.

Their only responsibilities are to:

* Receive HTTP requests.
* Validate input using **Form Requests**.
* Delegate business logic to a **Service**.
* Return responses through **Laravel API Resources**.

**Controllers must never contain business logic.**

---

### Services

Services contain the application's business logic.

Responsibilities include:

* Executing business rules.
* Coordinating one or more Repositories.
* Handling integrations with external services.
* Performing domain-specific validations.
* Managing database transactions when necessary.

**Services should not be aware of HTTP-related concerns.**

---

### Repositories

Repositories are responsible exclusively for data persistence.

Responsibilities include:

* Database queries.
* Creating records.
* Updating records.
* Deleting records.
* Complex queries.
* Pagination.
* Filtering.

**Repositories must never contain business logic.**

---

### Form Requests

All input validation must be handled through **Laravel Form Requests**.

Avoid placing validation logic inside Controllers.

---

### API Resources

All API responses must be returned through **Laravel API Resources**.

Never expose Eloquent Models directly.

Resources are responsible for:

* Transforming data.
* Standardizing API responses.
* Hiding internal attributes.
* Providing a consistent output structure.

---

### Policies

Authorization must always be handled using **Laravel Policies**.

Avoid scattering permission checks throughout the codebase.

---

## Core Principles

* Thin Controllers.
* Fat Services.
* Repositories are responsible only for data access.
* API Resources handle response serialization.
* Form Requests handle validation.
* Policies handle authorization.
* Low coupling.
* High cohesion.
* Predictable and consistent code.

---

## Coding Standards

Always follow:

* PSR-12.
* Official Laravel best practices.
* Clear and meaningful naming.
* Small, focused methods.
* Single Responsibility Principle (SRP).
* Don't Repeat Yourself (DRY).
* Prefer composition over inheritance.

---

## API Documentation

All endpoints must be documented using **Scalar**.

Whenever an endpoint is created or modified:

* Update the API documentation.
* Document request parameters.
* Document response payloads.
* Document possible error responses.

---

## Quality Standards

Before completing any change:

* Run the automated test suite.
* Run Laravel Pint.
* Ensure no existing functionality has been broken.
* Preserve the current architecture and coding patterns.
* Avoid introducing unnecessary dependencies.

---

## Commands

### Install dependencies

```bash
composer install
```

### Run the test suite

```bash
composer test
```

### Format the code

```bash
./vendor/bin/pint
```

### Run database migrations

```bash
php artisan migrate
```

### Start the development server

```bash
php artisan serve
```

---

## Before Implementing a New Feature

Always follow this workflow:

1. Understand the existing architecture.
2. Check whether a similar implementation already exists.
3. Reuse existing Services and Repositories whenever possible.
4. Follow the architectural guidelines defined in this document.
5. Create or update the necessary automated tests.
6. Run Laravel Pint and the test suite before considering the task complete.
7. Update the API documentation whenever required.

---

## AI Agent Guidelines

When working on the NeuroFlow backend:

* Respect the existing architecture before proposing new patterns.
* Do not refactor code solely based on personal preference.
* Prioritize consistency over unnecessary innovation.
* Reuse existing abstractions whenever possible.
* Introduce new abstractions only when they provide clear long-term value.
* Keep solutions simple, maintainable, and aligned with the project's architecture.
