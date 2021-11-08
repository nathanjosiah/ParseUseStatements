<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Kdyby\ParseUseStatements\UseStatements;
use PHPUnit\Framework\TestCase;

class UseStatementsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../files/noNamespace.php';
        require_once __DIR__ . '/../files/bracketedNamespace.php';
        require_once __DIR__ . '/../files/inNamespace.php';
        require_once __DIR__ . '/../files/twoBlocks.php';
        require_once __DIR__ . '/../files/nonClassUse.php';
        require_once __DIR__ . '/../files/groupUse.php';
    }

    public function testUseStatements()
    {
        $rcNoNamespace = new \ReflectionClass(\NoNamespace::class);
        $rcBTest = new \ReflectionClass(\BTest::class);
        $rcFoo = new \ReflectionClass(\Test\Space\Foo::class);
        $rcBar = new \ReflectionClass(\Test\Space\Bar::class);

        self::assertSame('A', UseStatements::expandClassName('A', $rcNoNamespace));
        self::assertSame('A\B', UseStatements::expandClassName('C', $rcNoNamespace));
        self::assertSame('BTest', UseStatements::expandClassName('BTest', $rcBTest));
        self::assertSame('Test\Space\Foo', UseStatements::expandClassName('self', $rcFoo));
        self::assertSame('Test\Space\Foo', UseStatements::expandClassName('Self', $rcFoo));
        self::assertSame('Test\Space\Foo', UseStatements::expandClassName('static', $rcFoo));
        self::assertSame('Test\Space\Foo', UseStatements::expandClassName('$this', $rcFoo));

        foreach (['String', 'string', 'int', 'float', 'bool', 'array', 'callable'] as $type) {
            self::assertSame(strtolower($type), UseStatements::expandClassName($type, $rcFoo));
        }

        /*
        alias to expand => [
            FQN for $rcFoo,
            FQN for $rcBar
        ]
        */
        $cases = [
            '\Absolute' => [
                'Absolute',
                'Absolute',
            ],
            '\Absolute\Foo' => [
                'Absolute\Foo',
                'Absolute\Foo',
            ],
            'AAA' => [
                'Test\Space\AAA',
                'AAA',
            ],
            'AAA\Foo' => [
                'Test\Space\AAA\Foo',
                'AAA\Foo',
            ],
            'B' => [
                'Test\Space\B',
                'BBB',
            ],
            'B\Foo' => [
                'Test\Space\B\Foo',
                'BBB\Foo',
            ],
            'DDD' => [
                'Test\Space\DDD',
                'CCC\DDD',
            ],
            'DDD\Foo' => [
                'Test\Space\DDD\Foo',
                'CCC\DDD\Foo',
            ],
            'F' => [
                'Test\Space\F',
                'EEE\FFF',
            ],
            'F\Foo' => [
                'Test\Space\F\Foo',
                'EEE\FFF\Foo',
            ],
            'HHH' => [
                'Test\Space\HHH',
                'Test\Space\HHH',
            ],
            'Notdef' => [
                'Test\Space\Notdef',
                'Test\Space\Notdef',
            ],
            'Notdef\Foo' => [
                'Test\Space\Notdef\Foo',
                'Test\Space\Notdef\Foo',
            ],
            // trim leading backslash
            'G' => [
                'Test\Space\G',
                'GGG',
            ],
            'G\Foo' => [
                'Test\Space\G\Foo',
                'GGG\Foo',
            ],
        ];

        foreach ($cases as $alias => $fqn) {
            self::assertSame($fqn[0], UseStatements::expandClassName($alias, $rcFoo), "Case '$alias'=>'${fqn[0]}' [0]");
            self::assertSame($fqn[1], UseStatements::expandClassName($alias, $rcBar), "Case '$alias'=>'${fqn[1]}' [1]");
        }

        self::assertSame(
            ['C' => 'A\B'],
            UseStatements::getUseStatements(new ReflectionClass(NoNamespace::class))
        );
        self::assertSame(
            [],
            UseStatements::getUseStatements(new ReflectionClass(Test\Space\Foo::class))
        );
        self::assertSame(
            ['AAA' => 'AAA', 'B' => 'BBB', 'DDD' => 'CCC\DDD', 'F' => 'EEE\FFF', 'G' => 'GGG'],
            UseStatements::getUseStatements(new ReflectionClass(Test\Space\Bar::class))
        );
        self::assertSame(
            [],
            UseStatements::getUseStatements(new ReflectionClass(stdClass::class))
        );
    }

    public function testNonClassUse()
    {
        self::assertSame(
            [],
            UseStatements::getUseStatements(new ReflectionClass(\NonClassUseTest::class))
        );
    }

    public function testGroupUse()
    {
        self::assertSame(
            ['A' => 'A\B\A', 'C' => 'A\B\B\C', 'D' => 'A\B\C', 'E' => 'D\E'],
            UseStatements::getUseStatements(new ReflectionClass(\GroupUseTest::class))
        );
    }
}
