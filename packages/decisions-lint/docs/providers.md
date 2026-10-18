# Providers

decisions-lint asks a model typed questions and reads back probabilities. Two kinds of provider answer them. A **decision API** (Jev, OpenAI Decisions) is built for exactly this and answers in one short request. A **chat model** served locally (Ollama, LocalAI, or any OpenAI-compatible server) is asked the same questions in a prompt and constrained to answer with one JSON object that follows a schema; it is slower and its numbers are self-reported, but it runs on your own hardware with any open model. Every provider sees the same questions and the same source; the adapter translates between the linter's `Decision` interface and the API.

| `provider.name` | Kind | Default endpoint | Default model | API key |
|-----------------|------|------------------|---------------|---------|
| `jev` | decision API | `https://api.typesafe.ai/v1/systemone` | `jev-latest` | required, `TYPESAFE_API_KEY` |
| `openai` | decision API | `https://api.openai.com/v1/decisions` | `gpt-6-luna` | required, `OPENAI_API_KEY` |
| `ollama` | chat model | `http://localhost:11434/api/chat` | `llama3.1` | optional, `OLLAMA_API_KEY` |
| `localai` | chat model | `http://localhost:8080/v1/chat/completions` | `gpt-4` | optional, `LOCALAI_API_KEY` |
| `openai-compatible` | chat model | `http://localhost:8000/v1/chat/completions` | `default` | optional, `OPENAI_COMPATIBLE_API_KEY` |

Hosted decision APIs default to a 60-second timeout and eight requests in flight. Local chat models generate their answer token by token, so they default to a 300-second timeout and two requests in flight; raise `provider.concurrency` when the server has the capacity.

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

## Ollama

```yaml
provider:
  name: ollama
  model: llama3.1
  base_url: http://localhost:11434
```

The adapter posts to Ollama's native chat endpoint, `/api/chat`, with `stream: false`, a zero temperature and the answer schema as `format`, which Ollama enforces during generation. Pull the model first (`ollama pull llama3.1`). An `OLLAMA_API_KEY` variable, when set, is sent as a bearer token for Ollama Cloud or a protected proxy; it is not needed for a local server.

## LocalAI and other OpenAI-compatible servers

```yaml
provider:
  name: localai
  model: gpt-4
  base_url: http://localhost:8080
```

`localai` and `openai-compatible` share one adapter that posts to `/v1/chat/completions` with a `response_format` of type `json_schema`, `strict: true` and a zero temperature. LocalAI's all-in-one images alias `gpt-4` to a bundled model; set `provider.model` to the model your server loads. The same adapter works with vLLM, llama.cpp's server, LM Studio and hosted gateways that implement the API; use `openai-compatible` with the server's `base_url` and, when it needs one, an API key in `OPENAI_COMPATIBLE_API_KEY`.

### How a chat model is asked

The conversation opens with a fixed system message that asks for calibrated probabilities and nothing but JSON. The user message holds the subject sections (file, kind, name, signature, declaration, and the class or file source) followed by a `## Questions` section that writes out each rule's instructions and, for a choice, the options with their descriptions, or for a score, the numbered levels. The schema requires one object per question:

| Question | Object |
|----------|--------|
| predicate | `{"probability": 0..1}` |
| choice | `{"choice": "<option>", "confidence": 0..1}` |
| score | `{"score": <level>, "confidence": 0..1}` |

A model that answers with text instead of JSON, or with an option the question did not list, is reported or treated as a refusal rather than guessed at. Small models are weaker judges than a decision model; start with `severity: warning`, compare findings against your own judgement, and raise `threshold` until they are ones you would act on.

## Fakes for tests

`DecisionsLint\Provider\Fake` ships one fake per provider so a rule set, a reporter or a CI pipeline can be exercised without a server: `FakeJevTransport`, `FakeOpenAiTransport`, `FakeOllamaTransport` and `FakeChatCompletionsTransport` each read a real request of their API and answer in that API's shape; `FakeDecision` answers in memory, and `FakeTransport` replays canned HTTP responses. An `AnswerScript` decides the answers from the question and the subject, and defaults to the answer that never violates a rule. Inject a fake through `Application`'s third constructor argument or `CheckRun`'s first.

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

- `Provider\Jev\JevClient`, `Provider\OpenAi\OpenAiClient`, `Provider\Ollama\OllamaClient` and `Provider\OpenAiCompatible\ChatCompletionsClient` build and post the provider's own request documents through a shared `JsonEndpoint`.
- `Provider\Jev\JevDecision`, `Provider\OpenAi\OpenAiDecision`, `Provider\Ollama\OllamaDecision` and `Provider\OpenAiCompatible\ChatCompletionsDecision` implement `Decision\Decision`: they turn each `Inquiry` into a client request and each response into a `Verdict` of typed `Answer`s. The chat adapters share `Provider\Chat\ChatPrompt` and `Provider\Chat\ChatAnswerReader`.
- `Provider\ProviderFactory` picks the adapter from `provider.name` and reads the API key from the environment.

A new provider adds a client, an adapter that implements `Decision`, a `ProviderSettings::defaults()` entry, a case in the factory, the name in `schema/decisions.schema.json`, and a fake transport for its tests. The linter, the rules and the reporters do not change.
