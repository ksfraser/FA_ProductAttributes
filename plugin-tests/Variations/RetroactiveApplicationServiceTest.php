<?php

namespace Ksfraser\FA_ProductAttributes\Variations\Test\Service;

use Ksfraser\FA_ProductAttributes\Variations\Service\RetroactiveApplicationService;
use Ksfraser\FA_ProductAttributes\Dao\ProductAttributesDao;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class RetroactiveApplicationServiceTest extends TestCase
{
    public function testScanForVariationsSimplePattern(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        // Mock stock IDs with a clear pattern
        $faDb->method('getTablePrefix')->willReturn('fa_');
        $faDb->expects($this->once())
            ->method('query')
            ->with('SELECT stock_id FROM `fa_stock_master` ORDER BY stock_id')
            ->willReturn([
                ['stock_id' => 'ABC-S'],
                ['stock_id' => 'ABC-M'],
                ['stock_id' => 'ABC-L'],
                ['stock_id' => 'XYZ-RED'],
                ['stock_id' => 'XYZ-BLUE']
            ]);

        $dao->expects($this->any())
            ->method('listCategories')
            ->willReturn([]);

        $service = new RetroactiveApplicationService($dao, $faDb);
        $suggestions = $service->scanForVariations();

        $this->assertArrayHasKey('ABC-*', $suggestions);
        $this->assertArrayHasKey('XYZ-*', $suggestions);
        $this->assertEquals(['ABC-S', 'ABC-M', 'ABC-L'], $suggestions['ABC-*']['existing_variations']);
        $this->assertEquals(['XYZ-RED', 'XYZ-BLUE'], $suggestions['XYZ-*']['existing_variations']);
    }

    public function testIdentifyPatterns(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $stockIds = ['ABC-S', 'ABC-M', 'ABC-L', 'XYZ-RED', 'XYZ-BLUE', 'SINGLE'];
        $patterns = $this->invokePrivateMethod($service, 'identifyPatterns', [$stockIds]);

        $this->assertArrayHasKey('ABC-*', $patterns);
        $this->assertArrayHasKey('XYZ-*', $patterns);
        $this->assertEquals(['ABC-S', 'ABC-M', 'ABC-L'], $patterns['ABC-*']);
        $this->assertEquals(['XYZ-RED', 'XYZ-BLUE'], $patterns['XYZ-*']);
        $this->assertArrayNotHasKey('SINGLE-*', $patterns); // Single item filtered out
    }

    public function testAnalyzePatternConsistentAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->any())
            ->method('listCategories')
            ->willReturn([]);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $pattern = 'ABC-*';
        $stockIds = ['ABC-S-RED', 'ABC-M-BLUE', 'ABC-L-GREEN'];
        $analysis = $this->invokePrivateMethod($service, 'analyzePattern', [$pattern, $stockIds]);

        $this->assertNotNull($analysis);
        $this->assertEquals('ABC', $analysis['base_stock_id']);
        $this->assertEquals($stockIds, $analysis['existing_variations']);
        $this->assertCount(2, $analysis['attribute_groups']); // 2 attributes per variation
        $this->assertEquals(['L', 'M', 'S'], $analysis['attribute_groups'][0]); // Alphabetically sorted
        $this->assertEquals(['BLUE', 'GREEN', 'RED'], $analysis['attribute_groups'][1]); // Alphabetically sorted
    }

    public function testAnalyzePatternInconsistentAttributes(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $pattern = 'ABC-*';
        $stockIds = ['ABC-S', 'ABC-M-RED', 'ABC-L']; // Inconsistent attribute count
        $analysis = $this->invokePrivateMethod($service, 'analyzePattern', [$pattern, $stockIds]);

        $this->assertNull($analysis); // Should return null for inconsistent patterns
    }

    public function testAnalyzePatternNoMatchingStockIds(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $pattern = 'XYZ-*';
        $stockIds = ['ABC-S', 'ABC-M', 'ABC-L']; // None start with XYZ-
        $analysis = $this->invokePrivateMethod($service, 'analyzePattern', [$pattern, $stockIds]);

        $this->assertNull($analysis); // Should return null when no stock IDs match the base pattern
    }

    public function testFindBestCategoryMatchNoMatch(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $dao->expects($this->once())
            ->method('listValues')
            ->with(1)
            ->willReturn([
                ['id' => 1, 'value' => 'Small'],
                ['id' => 2, 'value' => 'Large']
            ]);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $values = ['Red', 'Blue', 'Green']; // No match with Size category values
        $match = $this->invokePrivateMethod($service, 'findBestCategoryMatch', [$values, [['id' => 1, 'code' => 'size', 'label' => 'Size']]]);

        $this->assertNull($match); // Should return null when no category matches
    }

    public function testSuggestValuesNoCategory(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $values = ['Red', 'Blue'];
        $suggested = $this->invokePrivateMethod($service, 'suggestValues', [$values, null]);

        $this->assertCount(2, $suggested);
        $this->assertEquals('Red', $suggested[0]['value']);
        $this->assertEquals('red', $suggested[0]['slug']);
        $this->assertNull($suggested[0]['category_id']);
        $this->assertEquals('Blue', $suggested[1]['value']);
        $this->assertEquals('blue', $suggested[1]['slug']);
        $this->assertNull($suggested[1]['category_id']);
    }

    public function testCalculateConfidence(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        // Perfect match: 2 sizes × 2 colors = 4 expected, 4 actual
        $stockIds = ['ABC-S-RED', 'ABC-S-BLUE', 'ABC-M-RED', 'ABC-M-BLUE'];
        $attributeGroups = [['S', 'M'], ['RED', 'BLUE']];
        $confidence = $this->invokePrivateMethod($service, 'calculateConfidence', [$stockIds, $attributeGroups]);

        $this->assertEquals(1.0, $confidence);

        // Partial match: 2×2=4 expected, 3 actual
        $stockIds = ['ABC-S-RED', 'ABC-S-BLUE', 'ABC-M-RED'];
        $confidence = $this->invokePrivateMethod($service, 'calculateConfidence', [$stockIds, $attributeGroups]);

        $this->assertEquals(0.75, $confidence);
    }

    public function testCalculateConfidenceEmptyGroups(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $stockIds = ['ABC'];
        $attributeGroups = []; // Empty groups
        $confidence = $this->invokePrivateMethod($service, 'calculateConfidence', [$stockIds, $attributeGroups]);

        $this->assertEquals(1.0, $confidence); // 1 expected variation (1^0 = 1), 1 actual
    }

    public function testCreateSlug(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $this->assertEquals('red', $this->invokePrivateMethod($service, 'createSlug', ['Red']));
        $this->assertEquals('extralarge', $this->invokePrivateMethod($service, 'createSlug', ['Extra Large']));
        $this->assertEquals('size42', $this->invokePrivateMethod($service, 'createSlug', ['Size 42']));
    }

    public function testCreateSlugEdgeCases(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $this->assertEquals('', $this->invokePrivateMethod($service, 'createSlug', ['']));
        $this->assertEquals('abc123', $this->invokePrivateMethod($service, 'createSlug', ['ABC!@#123']));
        $this->assertEquals('testvalue', $this->invokePrivateMethod($service, 'createSlug', ['Test-Value']));
    }

    public function testApplySuggestionValid(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = [
            'base_stock_id' => 'ABC',
            'existing_variations' => ['ABC-S', 'ABC-M', 'ABC-L']
        ];

        $result = $service->applySuggestion($suggestion);
        $this->assertTrue($result);
    }

    public function testApplySuggestionInvalid(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = []; // Invalid suggestion

        $result = $service->applySuggestion($suggestion);
        $this->assertFalse($result);
    }

    public function testApplySuggestionMissingBaseStockId(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = [
            'existing_variations' => ['ABC-S', 'ABC-M']
            // Missing base_stock_id
        ];

        $result = $service->applySuggestion($suggestion);
        $this->assertFalse($result);
    }

    public function testApplySuggestionEmptyVariations(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = [
            'base_stock_id' => 'ABC',
            'existing_variations' => [] // Empty variations
        ];

        $result = $service->applySuggestion($suggestion);
        $this->assertFalse($result);
    }

    public function testApplySuggestionWithCategories(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        // Expect DAO calls for adding assignments
        $dao->expects($this->exactly(2))
            ->method('addAssignment')
            ->withConsecutive(
                ['ABC', 1, 1, 0],
                ['ABC', 1, 2, 0]
            );

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = [
            'base_stock_id' => 'ABC',
            'existing_variations' => ['ABC-S', 'ABC-M'],
            'suggested_categories' => [
                0 => [
                    'suggested_category' => ['id' => 1],
                    'suggested_values' => [
                        ['id' => 1],
                        ['id' => 2]
                    ]
                ]
            ]
        ];

        $result = $service->applySuggestion($suggestion);
        $this->assertTrue($result);
    }

    public function testApplySuggestionDaoException(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        // Mock DAO to throw exception
        $dao->expects($this->once())
            ->method('addAssignment')
            ->willThrowException(new \Exception('Database error'));

        $service = new RetroactiveApplicationService($dao, $faDb);

        $suggestion = [
            'base_stock_id' => 'ABC',
            'existing_variations' => ['ABC-S'],
            'suggested_categories' => [
                0 => [
                    'suggested_category' => ['id' => 1],
                    'suggested_values' => [
                        ['id' => 1]
                    ]
                ]
            ]
        ];

        $result = $service->applySuggestion($suggestion);
        $this->assertFalse($result);
    }

    public function testScanForVariationsNoPatterns(): void
    {
        $dao = $this->createMock(ProductAttributesDao::class);
        $faDb = $this->createMock(DbAdapterInterface::class);

        $faDb->method('getTablePrefix')->willReturn('fa_');
        $faDb->expects($this->once())
            ->method('query')
            ->with('SELECT stock_id FROM `fa_stock_master` ORDER BY stock_id')
            ->willReturn([
                ['stock_id' => 'ABC'],
                ['stock_id' => 'XYZ'],
                ['stock_id' => 'SINGLE']
            ]);

        $service = new RetroactiveApplicationService($dao, $faDb);
        $suggestions = $service->scanForVariations();

        $this->assertEmpty($suggestions); // No patterns with multiple variations
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}