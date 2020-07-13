<?php declare(strict_types=1);

namespace WalleePayment\Resources\app\storefront\src\snippets\de_DE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface {
	public function getName(): string
	{
		return 'wallee.de-DE';
	}

	public function getPath(): string
	{
		return __DIR__ . '/wallee.de-DE.json';
	}

	public function getIso(): string
	{
		return 'de-DE';
	}

	public function getAuthor(): string
	{
		return 'wallee';
	}

	public function isBase(): bool
	{
		return false;
	}
}
