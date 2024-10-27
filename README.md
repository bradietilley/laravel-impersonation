# Impersonation

A simple yet flexible implementation of Impersonation Management in Laravel.

![Static Analysis](https://github.com/bradietilley/laravel-impersonation/actions/workflows/static.yml/badge.svg)
![Tests](https://github.com/bradietilley/laravel-impersonation/actions/workflows/tests.yml/badge.svg)
![Laravel Version](https://img.shields.io/badge/Laravel%20Version-%E2%89%A5%2011.0-F9322C)
![PHP Version](https://img.shields.io/badge/PHP%20Version-%E2%89%A5%208.3-4F5B93)

## Introduction

A simple impersonation manager that facilitates session-based impersonation of users, with support for recursive impersonation, if needed.


## Installation

```
composer require bradietilley/laravel-impersonation
```


## Documentation

The `BradieTilley\Impersonation\ImpersonationManager` singleton can be swapped out for your own.

Usage of this is fairly straight-forward, it can be depedency injected or statically called.

**Check if the authorised user can impersonate**

```php
ImpersonationManager::make()->canImpersonate($user);
```

**Begin impersonating a given user**

```php
ImpersonationManager::make()->impersonate($user);
```

**Check if the authorised user is currently impersonating**

```php
ImpersonationManager::make()->isImpersonating();
```

**Stop impersonating**

```php
ImpersonationManager::make()->stopImpersonating();
```

**Authorising who can impersonate**

In your service provider you should specify a callback to control who can impersonate.

```php
ImpersonationManager::authorizeUsing(function (User $admin, User $user) {
    return $admin->isAdmin() && ! $user->isAdmin();
});
```

This callback will be invoked as part of the `ImpersonationManager::make()->canImpersonate($user);` check, and will ultimately serve as a simple gate check for who can impersonate who.

## Author

- [Bradie Tilley](https://github.com/bradietilley)
