<?php

namespace Modules\Sviat\ProductRatingUpdater;

use Okay\Core\QueryFactory;
use Okay\Core\QueryFactory\SqlQuery;
use Okay\Entities\ProductsEntity;
use Okay\Modules\Sviat\ProductRatingUpdater\Backend\Helpers\BackendProductRatingUpdaterHelper as Helper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/Support/ModuleTestCase.php';

use Modules\Sviat\ProductRatingUpdater\Support\ModuleTestCase;

/**
 * Масове проставляння рейтингів товарам. Метод збирає UPDATE конкатенацією
 * рядків — єдине, що стоїть між цим і SQL-ін'єкцією, це типізована сигнатура
 * (?float/?int), яка не пропускає рядок далі. Тест фіксує і форму запиту, і
 * саму цю гарантію: послаблення типів у сигнатурі відкриє діру.
 *
 * Окремо фіксується руйнівний за замовчуванням режим: без $onlyMissing запит
 * іде без WHERE і переписує рейтинги ВСІХ товарів магазину.
 */
#[AllowMockObjectsWithoutExpectations]
class RecalcSqlTest extends ModuleTestCase
{
    /** @var string[] */
    private array $captured = [];

    private function buildHelper(int $affected = 0): Helper
    {
        $this->captured = [];

        $queryFactory = $this->createStub(QueryFactory::class);
        $queryFactory->method('newSqlQuery')->willReturnCallback(static fn () => new SqlQuery());

        $productsEntity = $this->mockEntity(ProductsEntity::class, ['find']);
        $productsEntity->method('find')->willReturn([]);

        return new Helper(
            $this->mockDatabase([], $this->captured, $affected),
            $queryFactory,
            $this->mockEntityFactory([ProductsEntity::class => $productsEntity])
        );
    }

    private function sqlFor(...$args): string
    {
        $helper = $this->buildHelper();
        $helper->recalc(...$args);

        return $this->captured[0] ?? '';
    }

    // --- форма запиту -------------------------------------------------------

    /** Без діапазонів проставляється фіксована п'ятірка й один голос. */
    public function testDefaultsWriteAFlatFiveStarRating(): void
    {
        self::assertSame('UPDATE __products SET rating = 5.0, votes = 1', $this->sqlFor());
    }

    public function testRatingRangeBecomesARandomExpression(): void
    {
        $sql = $this->sqlFor(false, 4.0, 5.0);

        self::assertStringContainsString('rating = (4 + RAND() * (5 - 4))', $sql);
    }

    public function testVotesRangeBecomesAFlooredRandomExpression(): void
    {
        $sql = $this->sqlFor(false, null, null, 3, 10);

        // +1 у верхній межі робить діапазон включним після FLOOR().
        self::assertStringContainsString('votes = FLOOR(3 + RAND() * (10 - 3 + 1))', $sql);
    }

    /**
     * Діапазон застосовується лише коли задані ОБИДВІ межі — половина діапазону
     * мовчки відкидається на користь фіксованого значення.
     */
    /** @dataProvider halfRangeProvider */
    #[DataProvider('halfRangeProvider')]
    public function testHalfSpecifiedRangeFallsBackToTheFixedValue(?float $min, ?float $max): void
    {
        $sql = $this->sqlFor(false, $min, $max);

        self::assertStringContainsString('rating = 5.0', $sql);
        self::assertStringNotContainsString('RAND()', $sql);
    }

    public static function halfRangeProvider(): array
    {
        return [
            'лише нижня' => [4.0, null],
            'лише верхня' => [null, 5.0],
        ];
    }

    // --- область дії --------------------------------------------------------

    /**
     * Режим «тільки порожні» звужує запит до товарів без рейтингу чи голосів.
     * Саме він має бути типовим вибором в адмінці.
     */
    public function testOnlyMissingNarrowsTheUpdate(): void
    {
        $sql = $this->sqlFor(true);

        self::assertStringContainsString(
            'WHERE rating IS NULL OR rating = 0 OR votes IS NULL OR votes = 0',
            $sql
        );
    }

    /**
     * А без нього WHERE немає взагалі — запит переписує рейтинги всього
     * каталогу, включно з реальними відгуками покупців. Поведінка навмисна,
     * але руйнівна, тож зафіксована явно.
     */
    public function testWithoutOnlyMissingTheUpdateHasNoWhereClauseAtAll(): void
    {
        self::assertStringNotContainsString('WHERE', $this->sqlFor(false));
    }

    public function testAffectedRowsAreReturned(): void
    {
        $helper = $this->buildHelper(affected: 137);

        self::assertSame(137, $helper->recalc(true));
    }

    public function testExactlyOneQueryIsIssued(): void
    {
        $helper = $this->buildHelper();
        $helper->recalc(true, 4.0, 5.0, 1, 10);

        self::assertCount(1, $this->captured);
    }

    // --- гарантія від ін'єкції ---------------------------------------------

    /**
     * Значення підставляються в SQL конкатенацією, тож єдиний захист — типи в
     * сигнатурі. Спроба передати рядок із SQL має впасти TypeError ЩЕ ДО того,
     * як щось потрапить у запит.
     */
    /** @dataProvider injectionAttemptProvider */
    #[DataProvider('injectionAttemptProvider')]
    public function testStringArgumentsAreRejectedByTheSignature(int $position, string $payload): void
    {
        $helper = $this->buildHelper();
        $args = [false, null, null, null, null];
        $args[$position] = $payload;

        $this->expectException(\TypeError::class);
        $helper->recalc(...$args);
    }

    public static function injectionAttemptProvider(): array
    {
        $payload = "5.0) , name = 'зламано' -- ";

        return [
            'нижня межа рейтингу' => [1, $payload],
            'верхня межа рейтингу' => [2, $payload],
            'нижня межа голосів'  => [3, $payload],
            'верхня межа голосів' => [4, $payload],
        ];
    }

    /**
     * Числові рядки з форми адмінки типи приводять до чисел, а не пропускають
     * як текст — у запиті опиняється число.
     */
    public function testNumericStringsAreCoercedToNumbers(): void
    {
        $sql = $this->sqlFor(false, '4.5', '5.0', '2', '8');

        self::assertStringContainsString('(4.5 + RAND() * (5 - 4.5))', $sql);
        self::assertStringContainsString('FLOOR(2 + RAND() * (8 - 2 + 1))', $sql);
        self::assertStringNotContainsString("'", $sql);
    }
}
