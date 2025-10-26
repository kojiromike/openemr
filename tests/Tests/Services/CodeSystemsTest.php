<?php

/**
 * CodeSystemsTest.php
 *
 * Tests for external code system loading functionality (RXNORM, SNOMED, ICD10, CQM_VALUESET)
 * Tests the functions in library/standard_tables_capture.inc.php
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Tests\Services;

use PHPUnit\Framework\TestCase;

class CodeSystemsTest extends TestCase
{
    private string $testTempDir;
    private ?string $originalTempDir = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for testing
        $this->testTempDir = sys_get_temp_dir() . '/openemr_vocab_test_' . uniqid();
        mkdir($this->testTempDir, 0777, true);

        // Store and override GLOBALS temporary directory
        if (isset($GLOBALS['temporary_files_dir'])) {
            $this->originalTempDir = $GLOBALS['temporary_files_dir'];
        }
        $GLOBALS['temporary_files_dir'] = $this->testTempDir;

        // Load the standard_tables_capture functions
        require_once(__DIR__ . '/../../../library/standard_tables_capture.inc.php');
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testTempDir)) {
            $this->recursiveRemoveDirectory($this->testTempDir);
        }

        // Restore original temp directory
        if ($this->originalTempDir !== null) {
            $GLOBALS['temporary_files_dir'] = $this->originalTempDir;
        }

        parent::tearDown();
    }

    /**
     * Helper to recursively remove a directory
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Helper to create a test zip file
     */
    private function createTestZipFile(string $filename, array $contents = []): string
    {
        $zipPath = $this->testTempDir . '/' . $filename;
        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Failed to create test zip file: $zipPath");
        }

        // Add default content if none provided
        if (empty($contents)) {
            $contents = ['test.txt' => 'test content'];
        }

        foreach ($contents as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $zipPath;
    }

    // ========================================================================
    // temp_copy() tests
    // ========================================================================

    public function testTempCopyCreatesDirectory(): void
    {
        $sourceFile = $this->testTempDir . '/source.txt';
        file_put_contents($sourceFile, 'test content');

        $result = temp_copy($sourceFile, 'RXNORM');

        $this->assertTrue($result);
        $this->assertDirectoryExists($this->testTempDir . '/RXNORM');
        $this->assertFileExists($this->testTempDir . '/RXNORM/source.txt');
    }

    public function testTempCopyWithExistingDirectory(): void
    {
        mkdir($this->testTempDir . '/SNOMED', 0777, true);

        $sourceFile = $this->testTempDir . '/snomed.zip';
        file_put_contents($sourceFile, 'snomed content');

        $result = temp_copy($sourceFile, 'SNOMED');

        $this->assertTrue($result);
        $this->assertFileExists($this->testTempDir . '/SNOMED/snomed.zip');
        $this->assertEquals('snomed content', file_get_contents($this->testTempDir . '/SNOMED/snomed.zip'));
    }

    public function testTempCopyWithNonexistentFile(): void
    {
        $result = temp_copy('/nonexistent/file.txt', 'ICD10');

        $this->assertFalse($result);
    }

    public function testTempCopyPreservesFilename(): void
    {
        $sourceFile = $this->testTempDir . '/RxNorm_full_01012024.zip';
        file_put_contents($sourceFile, 'rxnorm data');

        temp_copy($sourceFile, 'RXNORM');

        $this->assertFileExists($this->testTempDir . '/RXNORM/RxNorm_full_01012024.zip');
    }

    // ========================================================================
    // temp_unarchive() tests
    // ========================================================================

    public function testTempUnarchiveExtractsZipFile(): void
    {
        $zipFilename = 'test.zip';
        $zipPath = $this->createTestZipFile($zipFilename, [
            'file1.txt' => 'content1',
            'file2.txt' => 'content2'
        ]);

        mkdir($this->testTempDir . '/RXNORM', 0777, true);
        copy($zipPath, $this->testTempDir . '/RXNORM/' . $zipFilename);

        $result = temp_unarchive($zipFilename, 'RXNORM');

        $this->assertTrue($result);
        $this->assertFileExists($this->testTempDir . '/RXNORM/file1.txt');
        $this->assertFileExists($this->testTempDir . '/RXNORM/file2.txt');
        $this->assertEquals('content1', file_get_contents($this->testTempDir . '/RXNORM/file1.txt'));
    }

    public function testTempUnarchiveICD10SpecialHandling(): void
    {
        $zipFilename = 'icd10.zip';
        $zipPath = $this->createTestZipFile($zipFilename, [
            'subdir/codes.txt' => 'ICD10 codes',
            'readme.txt' => 'readme'
        ]);

        mkdir($this->testTempDir . '/ICD10', 0777, true);
        copy($zipPath, $this->testTempDir . '/ICD10/' . $zipFilename);

        $result = temp_unarchive($zipFilename, 'ICD10');

        $this->assertTrue($result);
        // ICD10 extraction flattens the structure
        $this->assertFileExists($this->testTempDir . '/ICD10/codes.txt');
        $this->assertFileExists($this->testTempDir . '/ICD10/readme.txt');
    }

    public function testTempUnarchiveWithNonexistentFile(): void
    {
        $result = temp_unarchive('nonexistent.zip', 'SNOMED');

        $this->assertFalse($result);
    }

    // ========================================================================
    // rmdir_recursive() tests
    // ========================================================================

    public function testRmdirRecursiveRemovesEmptyDirectory(): void
    {
        $testDir = $this->testTempDir . '/empty_dir';
        mkdir($testDir);

        rmdir_recursive($testDir);

        $this->assertDirectoryDoesNotExist($testDir);
    }

    public function testRmdirRecursiveRemovesDirectoryWithFiles(): void
    {
        $testDir = $this->testTempDir . '/dir_with_files';
        mkdir($testDir);
        file_put_contents($testDir . '/file1.txt', 'content');
        file_put_contents($testDir . '/file2.txt', 'content');

        rmdir_recursive($testDir);

        $this->assertDirectoryDoesNotExist($testDir);
    }

    public function testRmdirRecursiveRemovesNestedDirectories(): void
    {
        $testDir = $this->testTempDir . '/nested';
        mkdir($testDir . '/level1/level2/level3', 0777, true);
        file_put_contents($testDir . '/level1/file1.txt', 'content');
        file_put_contents($testDir . '/level1/level2/file2.txt', 'content');
        file_put_contents($testDir . '/level1/level2/level3/file3.txt', 'content');

        rmdir_recursive($testDir);

        $this->assertDirectoryDoesNotExist($testDir);
    }

    // ========================================================================
    // getFileData() tests
    // ========================================================================

    public function testGetFileDataReturnsGenerator(): void
    {
        $testFile = $this->testTempDir . '/test.txt';
        file_put_contents($testFile, "line1\nline2\nline3");

        $generator = getFileData($testFile);

        $this->assertInstanceOf(\Generator::class, $generator);
    }

    public function testGetFileDataReadsAllLines(): void
    {
        $testFile = $this->testTempDir . '/test.txt';
        $content = "line1\nline2\nline3\n";
        file_put_contents($testFile, $content);

        $generator = getFileData($testFile);
        $lines = iterator_to_array($generator);

        $this->assertCount(3, $lines);
        $this->assertEquals("line1\n", $lines[0]);
        $this->assertEquals("line2\n", $lines[1]);
        $this->assertEquals("line3\n", $lines[2]);
    }

    public function testGetFileDataWithEmptyFile(): void
    {
        $testFile = $this->testTempDir . '/empty.txt';
        file_put_contents($testFile, '');

        $generator = getFileData($testFile);
        $lines = iterator_to_array($generator);

        $this->assertCount(0, $lines);
    }

    public function testGetFileDataWithLargeFile(): void
    {
        $testFile = $this->testTempDir . '/large.txt';
        $lineCount = 1000;

        $handle = fopen($testFile, 'w');
        for ($i = 0; $i < $lineCount; $i++) {
            fwrite($handle, "Line $i\n");
        }
        fclose($handle);

        $generator = getFileData($testFile);
        $count = 0;
        foreach ($generator as $line) {
            $count++;
        }

        $this->assertEquals($lineCount, $count);
    }

    public function testGetFileDataWithNonexistentFile(): void
    {
        // getFileData will trigger a warning when trying to open a nonexistent file
        // Suppress the warning in the test
        set_error_handler(function ($errno, $errstr) {
            // Expected warning, do nothing
        });

        $generator = getFileData('/nonexistent/file.txt');

        $lines = iterator_to_array($generator);
        $this->assertCount(0, $lines);

        restore_error_handler();
    }

    // ========================================================================
    // temp_dir_cleanup() tests
    // ========================================================================

    public function testTempDirCleanupRemovesDirectory(): void
    {
        $testDir = $this->testTempDir . '/CLEANUP_TEST';
        mkdir($testDir, 0777, true);
        file_put_contents($testDir . '/test.txt', 'content');

        temp_dir_cleanup('CLEANUP_TEST');

        $this->assertDirectoryDoesNotExist($testDir);
    }

    public function testTempDirCleanupWithNestedFiles(): void
    {
        $testDir = $this->testTempDir . '/NESTED_CLEANUP';
        mkdir($testDir . '/subdir', 0777, true);
        file_put_contents($testDir . '/file1.txt', 'content');
        file_put_contents($testDir . '/subdir/file2.txt', 'content');

        temp_dir_cleanup('NESTED_CLEANUP');

        $this->assertDirectoryDoesNotExist($testDir);
    }

    // ========================================================================
    // Integration tests
    // ========================================================================

    public function testTempCopyAndUnarchiveIntegration(): void
    {
        $zipPath = $this->createTestZipFile('integration.zip', [
            'test1.txt' => 'content1',
            'test2.txt' => 'content2'
        ]);

        $copyResult = temp_copy($zipPath, 'INTEGRATION');
        $this->assertTrue($copyResult);

        $unarchiveResult = temp_unarchive('integration.zip', 'INTEGRATION');
        $this->assertTrue($unarchiveResult);

        $this->assertFileExists($this->testTempDir . '/INTEGRATION/test1.txt');
        $this->assertFileExists($this->testTempDir . '/INTEGRATION/test2.txt');
    }

    public function testCompleteWorkflowWithCleanup(): void
    {
        $zipPath = $this->createTestZipFile('workflow.zip', ['data.txt' => 'test']);

        temp_copy($zipPath, 'WORKFLOW');
        temp_unarchive('workflow.zip', 'WORKFLOW');

        $this->assertDirectoryExists($this->testTempDir . '/WORKFLOW');
        $this->assertFileExists($this->testTempDir . '/WORKFLOW/data.txt');

        temp_dir_cleanup('WORKFLOW');

        $this->assertDirectoryDoesNotExist($this->testTempDir . '/WORKFLOW');
    }

    // ========================================================================
    // Filename pattern validation tests (from list_staged.php logic)
    // ========================================================================

    public function testRxnormFilenamePattern(): void
    {
        $validFiles = [
            'RxNorm_full_01012024.zip',
            'RxNorm_full_12312023.zip',
        ];

        $invalidFiles = [
            'RxNorm_01012024.zip',
            'rxnorm_full_01012024.zip',
        ];

        $pattern = "/RxNorm_full_([0-9]{8})\.zip/";

        foreach ($validFiles as $filename) {
            $this->assertMatchesRegularExpression($pattern, $filename);
        }

        foreach ($invalidFiles as $filename) {
            $this->assertDoesNotMatchRegularExpression($pattern, $filename);
        }
    }

    public function testRxnormDateExtraction(): void
    {
        $filename = 'RxNorm_full_01152024.zip';
        preg_match("/RxNorm_full_([0-9]{8})\.zip/", $filename, $matches);

        $dateString = $matches[1];
        $date_release = substr($dateString, 4) . "-" . substr($dateString, 0, 2) . "-" . substr($dateString, 2, -4);

        $this->assertEquals('2024-01-15', $date_release);
    }

    public function testSnomedInternationalFilenamePatterns(): void
    {
        $patterns = [
            'SnomedCT_INT_20240101.zip',
            'SnomedCT_Release_INT_20240101.zip',
            'SnomedCT_RF1Release_INT_20240101.zip',
            'SnomedCT_InternationalRF2_PRODUCTION_20240101T120000Z.zip',
        ];

        foreach ($patterns as $filename) {
            $matched = preg_match("/SnomedCT_INT_([0-9]{8})\.zip/", $filename) ||
                      preg_match("/SnomedCT_Release_INT_([0-9]{8})\.zip/", $filename) ||
                      preg_match("/SnomedCT_RF1Release_INT_([0-9]{8})\.zip/", $filename) ||
                      preg_match("/SnomedCT_InternationalRF2_PRODUCTION_([0-9]{8})[0-9a-zA-Z]{8}\.zip/", $filename);

            $this->assertTrue($matched, "Should match SNOMED pattern: $filename");
        }
    }

    public function testSnomedUSExtensionFilenamePatterns(): void
    {
        $patterns = [
            'SnomedCT_USEditionRF2_PRODUCTION_20240301T120000Z.zip',
            'SnomedCT_ManagedServiceUS_PRODUCTION_US1000124_20240301T120000Z.zip',
        ];

        foreach ($patterns as $filename) {
            $matched = preg_match("/SnomedCT_USEditionRF2_PRODUCTION_([0-9]{8})[0-9a-zA-Z]{8}\.zip/", $filename) ||
                      preg_match("/SnomedCT_ManagedServiceUS_PRODUCTION_US[0-9]{7}_([0-9a-zA-Z]{8})T[0-9Z]{7}\.zip/", $filename);

            $this->assertTrue($matched, "Should match SNOMED US pattern: $filename");
        }
    }

    public function testCqmValuesetFilenamePattern(): void
    {
        $validFiles = [
            'ep_ec_only_cms_20240101.xml.zip',
            'ec_only_cms_20231215.xml.zip',
        ];

        $pattern = "/e[p,c]_.*_cms_([0-9]{8})\.xml\.zip/";

        foreach ($validFiles as $filename) {
            $this->assertMatchesRegularExpression($pattern, $filename);
        }
    }

    public function testCqmValuesetDateExtraction(): void
    {
        // CQM valueset files use MMDDYYYY format
        $filename = 'ep_ec_only_cms_03152024.xml.zip';
        preg_match("/e[p,c]_.*_cms_([0-9]{8})\.xml\.zip/", $filename, $matches);

        $dateString = $matches[1]; // 03152024
        // The current implementation extracts: YYYY-MM-DD from MMDDYYYY
        // substr($dateString, 4, 4) gets YYYY, substr($dateString, 0, 2) gets MM, substr($dateString, 2, 2) gets DD
        $date_release = substr($dateString, 4) . "-" . substr($dateString, 0, 2) . "-" . substr($dateString, 2, 2);

        $this->assertEquals('2024-03-15', $date_release);
    }

    public function testVersionComparison(): void
    {
        $currentRevision = '2023-12-01';
        $fileRevisionDate = '2024-01-01';

        $currentTimestamp = strtotime($currentRevision);
        $fileTimestamp = strtotime($fileRevisionDate);

        $this->assertGreaterThan($currentTimestamp, $fileTimestamp);
    }
}
