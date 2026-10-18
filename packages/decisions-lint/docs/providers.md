# Providers

decisions-lint talks to a decision API: a model that answers typed questions with probabilities instead of generating text. Two are supported. Both see the same questions and the same source; the adapter translates between the linter's `Decision` interface and the API.

## Jev (TypeSafe AI)

```yaml
provider:
  name: jev
  model: jev-latest
  api_key_env: TYPESAFE_API_KEY
```

| Setting | Default |
|---------|---------|
| Endpoint | `POST https://api.typesafe.ai/v1/systemone` |
| Models | `jev-latest`, `jev-preview`, or a version such as `jev-1.13.0` |
| Key | `TYPESAFE_API_KEY` |

The subject sections become the request's `state` object, so the model sees `file`, `kind`, `name`, `signature`, `declaration` and the `class_source` or `file_source` under their own names. A predicate is sent as a `noul`, a choice keeps its options as `criteria`, and a score lists its levels as `criteria`. Jev answers every question of a request in parallel, so a request with five rules costs the same latency as one with one rule.

Jev's default limit is about 32,000 tokens for the state and one question. A class that large should be excluded or judged with `context: declaration`.

## OpenAI Decisions API

```yaml
provider:
  name: openai
  model: gpt-6-luna
  api_key_env: OPENAI_API_KEY
```

| Setting | Default |
|---------|---------|
| Endpoint | `POST https://api.openai.com/v1/decisions` |
| Models | `gpt-6-luna` |
| Key | `OPENAI_API_KEY` |

The subject sections are rendered into one Markdown input, with source sections fenced as PHP. A predicate is sent as a `predicate`, a choice as `choices` of `{value, description}`, and a score as `levels` of `{label, description}`. Answers are read back by question name; a `refusal` answer is treated as no answer, so no finding is reported for it.

## Requests, concurrency and failures

The linter sends one request per judged declaration and context, with every rule's question in it. Requests are sent `provider.concurrency` at a time over cURL, with `provider.timeout` seconds per request. `decisions-lint plan` prints how many requests a run will make before any is sent.

A failed request stops the run with exit `2` and a message that names the status, the provider's own error text and the setting to check:

| Status | Hint |
|--------|------|
| 401, 403 | Check the API key in the configured environment variable. |
| 404 | Check `provider.base_url`. |
| 400, 422 | Check `provider.model` and the rule questions. |
| 429 | Lower `provider.concurrency` or retry later. |
| 5xx | The provider is unavailable; retry later. |

A response in a shape the adapter does not understand is reported with the field that was missing or malformed.

## Adding a provider

The Provider layer separates the API client from the adapter:

- `Provider\Jev\JevClient` and `Provider\OpenAi\OpenAiClient` build and post the provider's own request documents through a shared `JsonEndpoint`.
- `Provider\Jev\JevDecision` and `Provider\OpenAi\OpenAiDecision` implement `Decision\Decision`: they turn each `Inquiry` into a client request and each response into a `Verdict` of typed `Answer`s.
- `Provider\ProviderFactory` picks the adapter from `provider.name` and reads the API key from the environment.

A new provider adds a client, an adapter that implements `Decision`, a `ProviderSettings::defaults()` entry, and a case in the factory. The linter, the rules and the reporters do not change.
