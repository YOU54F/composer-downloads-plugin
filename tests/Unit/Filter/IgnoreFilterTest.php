<?php

namespace LastCall\DownloadsPlugin\Tests\Unit\Filter;

use LastCall\DownloadsPlugin\Filter\FilterInterface;
use LastCall\DownloadsPlugin\Filter\IgnoreFilter;
use LastCall\DownloadsPlugin\Filter\TypeFilter;
use LastCall\DownloadsPlugin\Types;
use PHPUnit\Framework\MockObject\MockObject;

class IgnoreFilterTest extends BaseFilterTestCase
{
    private TypeFilter|MockObject $typeFilter;

    protected function setUp(): void
    {
        $this->typeFilter = $this->createMock(TypeFilter::class);
        parent::setUp();
    }

    public function testNotArchiveType(): void
    {
        $extraFile = ['ignore' => ['file']];
        $this->typeFilter->expects($this->once())->method('filter')->with($extraFile)->willReturn(Types::TYPE_FILE);
        $this->assertSame([], $this->filter->filter($extraFile));
    }

    public function getEmptyIgnoreTests(): array
    {
        return [
            [[]],
            [['ignore' => []]],
        ];
    }

    /**
     * @dataProvider getEmptyIgnoreTests
     */
    public function testEmptyIgnore(array $extraFile): void
    {
        $this->typeFilter->expects($this->once())->method('filter')->with($extraFile)->willReturn(Types::TYPE_ZIP);
        $this->assertSame([], $this->filter->filter($extraFile));
    }

    public function getInvalidIgnoreTests(): array
    {
        return [
            [true, 'bool'],
            [false, 'bool'],
            [123, 'int'],
            [12.3, 'float'],
            ['test', 'string'],
            [(object) ['key' => 'value'], 'stdClass'],
        ];
    }

    /**
     * @dataProvider getInvalidIgnoreTests
     */
    public function testInvalidIgnore(mixed $invalidIgnore, string $type): void
    {
        $extraFile = [
            'ignore' => $invalidIgnore,
        ];
        $this->parent->expects($this->once())->method('getName')->willReturn($this->parentName);
        $this->typeFilter->expects($this->once())->method('filter')->with($extraFile)->willReturn(Types::TYPE_ZIP);
        $this->expectUnexpectedValueException('ignore', sprintf('must be array, "%s" given', $type));
        $this->filter->filter($extraFile);
    }

    public function testFilterIgnore(): void
    {
        $ignore = ['dir/*', '!dir/file'];
        $extraFile = ['ignore' => $ignore];
        $this->typeFilter->expects($this->once())->method('filter')->with($extraFile)->willReturn(Types::TYPE_ZIP);
        $this->assertSame($ignore, $this->filter->filter($extraFile));
        $this->assertSame($ignore, $this->filter->filter([]));
    }

    protected function createFilter(): FilterInterface
    {
        return new IgnoreFilter($this->name, $this->parent, $this->typeFilter);
    }
}