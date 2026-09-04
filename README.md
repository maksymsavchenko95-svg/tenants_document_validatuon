# Tenant's Document Validation — Test Task
**PHP 8.4 · CakePHP 5.4 · SQLite · Vue 3**, in Docker, on `http://localhost:8765`.
Xdebug is pre-wired for PhpStorm.

---

## Run it

```bash
docker compose up
```

This runs the migration and the seed (the seed is idempotent), then starts the
server on `http://localhost:8765/tenants`. The seed creates two tenants:
**Acme** (three rules) and **Empty Corp** (no rules).

Tests:

```bash
docker compose exec app vendor/bin/phpunit --testsuite domain
```

```bash
docker compose exec app vendor/bin/phpunit --testsuite app
```

```bash
docker compose run --rm app composer test
```

---

## A note about the stack

I have not worked with CakePHP before, and my Vue experience is limited. Both
were listed as "nice to have" in the job description, so I took this task as a
chance to learn them and to show how I approach an unfamiliar stack.

Two things follow from this:

- I started from `cakephp/app`, the official application skeleton, and adapted it.
- I kept Vue as small as possible. There is no build step, no npm, no bundler.

---

## The main idea

**Dependency inversion**: The core does not depend on the framework. The
framework depends on the core.

The task is about validating documents against rules that belong to a tenant.
So I treated the validation logic as the core of the application, and CakePHP as
a delivery mechanism around it.

Everything in `src/DocumentValidation/` is plain PHP. It has **no `use` statement
pointing to CakePHP**, no database access, and no HTTP knowledge. It does not
know that a web framework exists.

Everything else in `src/` is the adapter layer, or the "shell".
Its job is to translate HTTP and SQL into domain objects, call
the domain, and translate the answer back.

---

## Design decisions

### 1. A rule is one class, and adding a rule changes nothing else

This is the "open for extension, closed for modification" principle, and it is
the decision the whole design is built around.

A rule implements `ValidationRule`.

`ValidationRule` extension: A rule that can be stored in the database also implements `ConfigurableRule`,
which adds `type()`, `fromConfig()` and `toConfig()`.

`RuleFactory` maps a type string to a class. The list of classes is passed into
its constructor, in `Application::services()`.

Every rule class is `final`. This is intentional. I want the design to be
extended by **adding a new class**, not by subclassing an existing rule and
overriding half of it.

### 2. Value objects are immutable, so the data cannot drift

`Document` is `final readonly`. It is a container: id, tenant id, content,
metadata. It has no behaviour and no setters. Once created, it is the same object
everywhere in the flow.

`ValidationResult` is also `final readonly`, and it protects one invariant:

```php
$this->isValid = $errors === [];
```

`isValid` is computed from `errors` in the constructor. There is no setter, no
`addError()`, and no way to have an empty error list with `isValid === false`, or
the opposite. `merge()` does not mutate anything, it returns a new instance.

This means the two fields can never disagree with each other. I did not have to
write a test for "isValid is consistent with errors", because the type system and
the constructor make the bad state impossible to build.

### 3. A rule reports a bad document, and throws only for a bad configuration

This is the rule I found most important while writing the code, so I want to
state it clearly.

There are two completely different kinds of "wrong" here:

- **The document is wrong.** It is returned as a `ValidationResult` with
  error messages.
- **The rule itself is wrong.**  It throws `InvalidRuleConfigException`.

The important part: a configuration problem is caught **once, at construction
time**, inside `fromConfig()`. It is never checked again during validation.
By the time a rule object exists, it is already known to be valid.

### 4. All rules always run, and all messages are collected

`DocumentValidator` starts from `ValidationResult::valid()` and merges the result
of every rule into it. Nothing short-circuits on the first failure, so user gets
all the errors.

### 5. An invalid document is a successful request

`POST /tenants/{id}/validate` returns **`200`** for a well-formed request, even
when the document is completely invalid. The body says `{"isValid": false,
"errors": [...]}`.

The reasoning: HTTP status describes the **request**, not the document. The
client asked "is this document valid?", and the server answered that question
correctly.

`4xx` is reserved for problems with the request itself:
- `400` — the body is not valid JSON, or `content` is not a string.
- `404` — the tenant does not exist.

### 6. One class touches storage

`DocumentValidator` depends on the `ValidationRuleProvider` interface.

That is the only seam between the validation flow and where rules come from.

`DatabaseRuleProvider` is the single implementation that reads the database. It
loads the stored `{type, config}` rows and rebuilds them through the same
`RuleFactory` used everywhere else. It lives in `src/Service/`, in the shell, not
in the domain.

`InMemoryRuleProvider` is the same interface backed by a plain array. Tests use
it. This is why the domain tests need no database.

### 8. What is stored is already normalised

The write path does not store what the user typed. It first builds the rule
through `RuleFactory`, and then persists the rule's own `toConfig()`.

So if a user submits `" Foo "` and the rule works with `"foo"`, the database
holds `"foo"`. Unknown keys are dropped. Invalid configuration is rejected with
`422` before anything is written.

The benefit is that the read path stays simple, and the database cannot contain
a rule that fails to load. Validation of configuration happens in exactly one
place, and it happens before writing, not after reading.a

---

## What I left out

I want to be explicit about the limits of this task, so they are read as
decisions and not as things I did not think about.

- **No authentication and no authorization.**
- **CSRF protection is disabled**
- **No pagination**
- **No rate limiting and no size limit on the request body.**
- **The document id is currently generated by the server** - not passed from front-end.
- **Rules are read on every request.** - not cached.

---

## Where to look

```
src/DocumentValidation/        the core — no framework, no database, no HTTP
  ValidationRule.php            interface: validate(Document): ValidationResult
  ValidationRuleProvider.php    interface: rulesFor(int): list<ValidationRule>
  ConfigurableRule.php          adds type() / fromConfig() / toConfig()
  Document.php                  final readonly value object
  ValidationResult.php          final readonly; isValid derived from errors
  DocumentValidator.php         resolves rules for a tenant, then runs them
  RuleFactory.php               (type, config) -> rule
  InMemoryRuleProvider.php      plain map, used by domain tests
  Rule/                         MaxDocumentSize, RequiredMetadata, ProhibitedWords,
                                MetadataAllowedValues, ContentPattern
  Exception/                    4 domain exceptions

src/                           the CakePHP adapter (the shell)
  Service/DatabaseRuleProvider  the only place validation meets the database
  Service/RuleSummarizer        rule -> one-line summary for the UI
  Controller/                   Tenants, TenantRules, DocumentValidation
  Model/                        Tenant, TenantValidationRule + tables
  Middleware/                   host header check, JSON body parsing

tests/TestCase/DocumentValidation/   the "domain" suite, runs without a database
tests/TestCase/{Controller,Model,Service}/   the "app" suite
```

---

## HTTP API

| Method   | Path                           | Body                   | Result                                              |
|----------|--------------------------------|------------------------|-----------------------------------------------------|
| `GET`    | `/tenants`                     | —                      | HTML (Vue island, data embedded)                    |
| `POST`   | `/tenants`                     | `{name}`               | `201 {id,name,created}` / `422 {errors:{name:[…]}}` |
| `DELETE` | `/tenants/{id}`                | —                      | `204` / `404`                                       |
| `GET`    | `/tenants/{id}/rules`          | —                      | HTML                                                |
| `POST`   | `/tenants/{id}/rules`          | `{type, config}`       | `201 {id,type,config,summary}` / `422` / `404`      |
| `DELETE` | `/tenants/{id}/rules/{ruleId}` | —                      | `204` / `404`                                       |
| `GET`    | `/tenants/{id}/validate`       | —                      | HTML                                                |
| `POST`   | `/tenants/{id}/validate`       | `{content, metadata?}` | `200 {isValid, errors}` / `400` / `404`             |

---
