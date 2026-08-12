<?php

namespace Support;

use Okay\Core\Database;
use Okay\Core\EntityFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Спільна база для тестів модулів.
 *
 * Майже кожен сервіс тут отримує EntityFactory і кличе ->get(FooEntity::class),
 * тож замість бази підставляється мапа FQCN → мок сутності. Той самий приклад
 * уже стоїть у PromoTestCase і StockSyncTestCase — цей клас зводить його в одне
 * місце для нових тестів; наявні два лишаються як є, вони зелені.
 *
 * Скрізь, де немає expects(), використовується createStub(), а не createMock():
 * PHPUnit 13 на кожен мок без очікувань пише нотис "No expectations were
 * configured", і кількасот таких рядків топлять корисний вивід.
 *
 * ВИНЯТОК — mockEntity(): їй потрібні одночасно onlyMethods() і
 * disableOriginalConstructor(), а це дає лише MockBuilder, тобто мок. Тест, що
 * кличе mockEntity(), мусить сам нести #[AllowMockObjectsWithoutExpectations]:
 * атрибут читається тільки з конкретного класу і з базового не успадковується.
 */
#[AllowMockObjectsWithoutExpectations]
abstract class ModuleTestCase extends TestCase
{
    /**
     * Мок EntityFactory, що мапить FQCN → мок сутності.
     *
     * @param array<class-string, object> $entityMocks
     */
    protected function mockEntityFactory(array $entityMocks): EntityFactory
    {
        $factory = $this->createStub(EntityFactory::class);
        $factory->method('get')->willReturnCallback(
            static fn (string $class) => $entityMocks[$class] ?? null
        );

        return $factory;
    }

    /**
     * Мок сутності з робочим fluent-ланцюжком.
     *
     * Дві пастки, обидві пекучі:
     *
     * 1. Мокати можна ЛИШЕ перелічені методи: Entity::$selectFields — приватне
     *    поле без значення за замовчуванням, тож на моці без конструктора
     *    справжній setSelectFields() падає на array_merge(null, ...). Описано в
     *    tests/Modules/Broken/StockSync/StockSyncTestCase.php.
     *
     * 2. Entity::mappedBy() оголошений final — PHPUnit його не підміняє. Тому
     *    ланцюжок фільтрується по тому, що реально можна замокати: final,
     *    статичні й неіснуючі методи відкидаються. Метод, який усередині кличе
     *    mappedBy(), протестувати цим моком не вийде — справжня реалізація
     *    звіряється з getFields(), якого на моці без конструктора немає.
     *
     * @param class-string $entityClass
     * @param string[] $extraMethods
     */
    protected function mockEntity(string $entityClass, array $extraMethods = [])
    {
        $chainMethods = array_values(array_filter(
            ['cols', 'noLimit', 'orderBy'],
            static function (string $method) use ($entityClass): bool {
                if (!method_exists($entityClass, $method)) {
                    return false;
                }
                $reflection = new \ReflectionMethod($entityClass, $method);
                if (PHP_VERSION_ID < 80100) { $reflection->setAccessible(true); }
                return !$reflection->isFinal() && !$reflection->isStatic();
            }
        ));

        $entity = $this->getMockBuilder($entityClass)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge($chainMethods, $extraMethods))
            ->getMock();

        foreach ($chainMethods as $method) {
            $entity->method($method)->willReturnSelf();
        }

        return $entity;
    }

    /**
     * Мок Database, що стрінгує кожен переданий у query() запит у $captured.
     *
     * @param array<int, mixed> $results
     * @param array<int, string> $captured  Заповнюється за посиланням.
     */
    protected function mockDatabase(array $results = [], array &$captured = [], int $affectedRows = 0): Database
    {
        $db = $this->getMockBuilder(Database::class)
            ->disableOriginalConstructor()
            ->getMock();

        $db->method('query')->willReturnCallback(function ($query) use (&$captured): bool {
            $captured[] = (string) $query;
            return true;
        });
        $db->method('results')->willReturn($results);
        $db->method('result')->willReturn($results[0] ?? null);
        $db->method('affectedRows')->willReturn($affectedRows);

        // Конструктор вимкнено, тож Database::$pdo лишається null, а деструктор
        // безумовно кличе $this->pdo->disconnect(). PHPUnit 13 доводить мок до
        // деструктора, тому підкладаємо заглушку з тим самим методом.
        $pdo = new class {
            public function disconnect(): void {}
        };
        $reflected = new \ReflectionProperty(Database::class, 'pdo');
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        $reflected->setValue($db, $pdo);

        return $db;
    }

    /** Викликає приватний/захищений метод. */
    protected function callPrivate(object $object, string $method, mixed ...$args): mixed
    {
        $reflected = new \ReflectionMethod($object, $method);
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        return $reflected->invokeArgs($object, $args);
    }

    /** Читає приватну/захищену властивість екземпляра. */
    protected function readPrivate(object $object, string $property): mixed
    {
        $reflected = new \ReflectionProperty($object, $property);
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        return $reflected->getValue($object);
    }

    /** Пише у приватну/захищену властивість екземпляра. */
    protected function writePrivate(object $object, string $property, mixed $value): void
    {
        $reflected = new \ReflectionProperty($object, $property);
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        if (PHP_VERSION_ID < 80100) { $reflected->setAccessible(true); }
        $reflected->setValue($object, $value);
    }

    /**
     * Створює екземпляр в обхід конструктора і заповнює властивості.
     *
     * Потрібно там, де конструктор ходить у базу або ServiceLocator.
     *
     * @param class-string $class
     * @param array<string, mixed> $properties
     */
    protected function instanceWithout(string $class, array $properties = []): object
    {
        $object = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $this->writePrivate($object, $name, $value);
        }

        return $object;
    }
}
