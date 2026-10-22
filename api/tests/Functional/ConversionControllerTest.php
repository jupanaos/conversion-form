<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConversionControllerTest extends WebTestCase
{
    private const URL = '/api/conversions';
    private const MAX_SIZE = 2 * 1024 * 1024;

    private KernelBrowser $client;

    /** @var string[] */
    private array $tmpFiles = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function testGetIsNotAllowed(): void
    {
        $this->client->request('GET', self::URL);

        $this->assertResponseStatusCodeSame(405);
    }

    // Success

    public function testValidUploadReturnsTheGoldenCsv(): void
    {
        $this->post($this->fixtureUpload('VENTE_ORIGINE.TXT'));

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(
            file_get_contents($this->fixturePath('VENTE_FINAL.csv')),
            $this->client->getResponse()->getContent(),
        );
    }

    public function testSuccessResponseHeaders(): void
    {
        $this->post($this->fixtureUpload('VENTE_ORIGINE.TXT'));

        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=utf-8');
        $this->assertResponseHeaderSame('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $this->client->getResponse()->headers->get('Cache-Control'));
        $this->assertStringContainsString(
            'attachment; filename=VENTE_ORIGINE.csv',
            $this->client->getResponse()->headers->get('Content-Disposition'),
        );
    }

    // Validation

    public function testMissingFileFieldReturns400(): void
    {
        $this->client->request('POST', self::URL);

        $this->assertError(400, 'invalid_file');
    }

    public function testFileSentAsArrayReturns400(): void
    {
        $this->client->request('POST', self::URL, [], ['file' => [$this->upload('a.txt', 'x')]]);

        $this->assertError(400, 'invalid_file');
    }

    public function testEmptyFileReturns400(): void
    {
        $this->post($this->upload('vide.txt', ''));

        $this->assertError(400, 'invalid_file');
    }

    public function testWrongExtensionReturns415(): void
    {
        $this->post($this->upload('ventes.pdf', 'abc'));

        $this->assertError(415, 'unsupported_file_type');
    }

    public function testFileOverSizeLimitReturns413(): void
    {
        $this->post($this->upload('gros.txt', str_repeat('a', self::MAX_SIZE + 1)));

        $this->assertError(413, 'file_too_large');
    }

    public function testBodyOverPostMaxSizeReturns413(): void
    {
        $this->client->request('POST', self::URL, [], [], ['CONTENT_LENGTH' => 3 * 1024 * 1024 + 1]);

        $this->assertError(413, 'file_too_large');
    }

    // Content validation

    public function testInvalidLineReportsItsNumber(): void
    {
        $lines = preg_split('/\r?\n/', file_get_contents($this->fixturePath('VENTE_ORIGINE.TXT')));
        $content = $lines[0]."\r\n".$lines[1]."\r\n".'trois;colonnes;seulement'."\r\n";

        $this->post($this->upload('invalide.txt', $content));

        $this->assertError(422, 'invalid_line');
        $this->assertSame(3, $this->errorBody()['line']);
    }

    public function testBinaryContentWithTxtExtensionIsRejected(): void
    {
        $this->post($this->upload('faux.txt', "\x00\x01\x02\xFF\xFE"));

        $this->assertResponseStatusCodeSame(422);
    }

    // Upload

    private function post(UploadedFile $file): void
    {
        $this->client->request('POST', self::URL, [], ['file' => $file]);
    }

    private function assertError(int $status, string $code): void
    {
        $this->assertResponseStatusCodeSame($status);
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertSame($code, $this->errorBody()['code']);
    }

    /** @return array{code: string, message: string, line: ?int} */
    private function errorBody(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true)['error'];
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__).'/Fixtures/'.$name;
    }

    private function validContent(): string
    {
        return file_get_contents($this->fixturePath('VENTE_ORIGINE.TXT'));
    }

    private function fixtureUpload(string $name): UploadedFile
    {
        return $this->upload($name, file_get_contents($this->fixturePath($name)));
    }

    private function upload(string $clientName, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ctl');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;

        return new UploadedFile($path, $clientName, null, null, true);
    }

    // Security

    public function testHostileFilenameCannotInjectHeaders(): void
    {
        $this->post($this->upload("a\"\r\nSet-Cookie: x=1.txt", $this->validContent()));

        $this->assertResponseStatusCodeSame(200);
        $headers = $this->client->getResponse()->headers;
        $this->assertFalse($headers->has('Set-Cookie'));
        $this->assertStringNotContainsString("\r", $headers->get('Content-Disposition'));
        $this->assertStringNotContainsString("\n", $headers->get('Content-Disposition'));
    }

    public function testPathInFilenameIsStripped(): void
    {
        $this->post($this->upload('../../etc/passwd.txt', $this->validContent()));

        $this->assertStringContainsString(
            'filename=passwd.csv',
            $this->client->getResponse()->headers->get('Content-Disposition'),
        );
    }

    public function testErrorsDoNotLeakServerDetails(): void
    {
        $this->post($this->upload('ventes.pdf', 'abc'));

        $body = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString(dirname(__DIR__, 2), $body);
        $this->assertStringNotContainsString('.php', $body);
        $this->assertStringNotContainsString('Stack trace', $body);
    }
}
