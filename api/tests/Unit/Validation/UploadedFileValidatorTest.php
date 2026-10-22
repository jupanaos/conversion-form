<?php

namespace App\Tests\Unit\Validation;

use App\Exception\FileTooLargeException;
use App\Exception\InvalidFileException;
use App\Exception\UnsupportedFileTypeException;
use App\Validation\UploadedFileValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileValidatorTest extends TestCase
{
    private const MAX_SIZE = 2097152;

    /** @var string[] */
    private array $tmpFiles = [];

    private UploadedFileValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UploadedFileValidator(self::MAX_SIZE, ['txt']);
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            @unlink($path);
        }
    }

    public function testValidFileIsReturned(): void
    {
        $file = $this->upload('ventes.txt', 'abc');

        $this->assertSame($file, $this->validator->validate($file));
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->validator->validate(null);
    }

    public function testFileSentAsArrayIsRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->validator->validate([$this->upload('ventes.txt', 'abc')]);
    }

    public function testNonFileValueIsRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->validator->validate('ventes.txt');
    }

    public function testUploadRejectedByPhpIniSizeIsReportedAsTooLarge(): void
    {
        $this->expectException(FileTooLargeException::class);

        $this->validator->validate($this->upload('ventes.txt', 'abc', UPLOAD_ERR_INI_SIZE));
    }

    public function testFailedUploadIsRejected(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->validator->validate($this->upload('ventes.txt', 'abc', UPLOAD_ERR_PARTIAL));
    }

    public function testFileAtSizeLimitIsAccepted(): void
    {
        $file = $this->upload('ventes.txt', str_repeat('a', self::MAX_SIZE));

        $this->assertSame($file, $this->validator->validate($file));
    }

    public function testFileOverSizeLimitIsRejected(): void
    {
        $this->expectException(FileTooLargeException::class);

        $this->validator->validate($this->upload('ventes.txt', str_repeat('a', self::MAX_SIZE + 1)));
    }

    public function testMissingFileWithOversizedBodyIsReportedAsTooLarge(): void
    {
        $this->expectException(FileTooLargeException::class);

        $this->validator->validate(null, self::MAX_SIZE + 1);
    }

    public function testMissingFileWithSmallBodyIsStillInvalid(): void
    {
        $this->expectException(InvalidFileException::class);

        $this->validator->validate(null, 100);
    }

    public function testExtensionIsCaseInsensitive(): void
    {
        $file = $this->upload('VENTE_ORIGINE.TXT', 'abc');

        $this->assertSame($file, $this->validator->validate($file));
    }

    public function testWrongExtensionIsRejected(): void
    {
        $this->expectException(UnsupportedFileTypeException::class);

        $this->validator->validate($this->upload('ventes.pdf', 'abc'));
    }

    public function testMissingExtensionIsRejected(): void
    {
        $this->expectException(UnsupportedFileTypeException::class);

        $this->validator->validate($this->upload('ventes', 'abc'));
    }

    public function testDoubleExtensionUsesTheLastOne(): void
    {
        $this->expectException(UnsupportedFileTypeException::class);

        $this->validator->validate($this->upload('ventes.txt.exe', 'abc'));
    }

    public function testSizeIsCheckedBeforeExtension(): void
    {
        $this->expectException(FileTooLargeException::class);

        $this->validator->validate($this->upload('ventes.pdf', str_repeat('a', self::MAX_SIZE + 1)));
    }

    private function upload(string $clientName, string $content, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($path, $content);
        $this->tmpFiles[] = $path;

        return new UploadedFile($path, $clientName, null, $error, true);
    }
}
