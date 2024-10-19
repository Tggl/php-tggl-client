<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://tggl.io/tggl-io-logo-white.svg">
    <img align="center" alt="Tggl Logo" src="https://tggl.io/tggl-io-logo-black.svg" width="200rem" />
  </picture>
</p>

<h1 align="center">Tggl PHP SDK</h1>

<p align="center">
  The PHP SDK can be used to evaluate flags and report usage to the Tggl API or a <a href="https://tggl.io/developers/evaluating-flags/tggl-proxy">proxy</a>.
</p>

<p align="center">
  <a href="https://tggl.io/">🔗 Website</a>
  •
  <a href="https://tggl.io/developers/sdks/php">📚 Documentation</a>
  •
  <a href="https://packagist.org/packages/tggl/client">📦 Packagist</a>
  •
  <a href="https://www.youtube.com/@Tggl-io">🎥 Videos</a>
</p>

## Usage

Install the dependency:

```bash
composer require tggl/client
```

Start evaluating flags:

```php
use Tggl\Client\TgglClient;
 
// Some class to represent your context
class Context {
  $userId;
  $email;
}
 
$client = new TgglClient('YOUR_API_KEY');
 
// An API call to Tggl is performed here
$flags = $client->evalContext(new Context());
 
if ($flags->isActive('my-feature')) {
  // ...
}
 
if ($flags->get('my-feature') === 'Variation A') {
  // ...
}
```
