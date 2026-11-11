<?php
use PhpWails\Runtime\Attribute\Export;
final class App {
    #[Export]
    public function greet(string $name = 'World'): string { return "Hello, {$name}!"; }
}
