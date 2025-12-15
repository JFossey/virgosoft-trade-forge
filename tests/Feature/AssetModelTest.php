<?php

namespace Tests\Feature;

use App\Enums\AssetSymbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_can_be_created_with_factory(): void
    {
        $asset = Asset::factory()->create();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'symbol' => $asset->symbol,
        ]);
    }

    public function test_asset_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();

        $this->assertEquals($user->id, $asset->user->id);
        $this->assertEquals($user->name, $asset->user->name);
    }

    public function test_user_has_many_assets(): void
    {
        $user = User::factory()->create();

        Asset::factory()->for($user)->symbol(AssetSymbol::BTC)->create();
        Asset::factory()->for($user)->symbol(AssetSymbol::ETH)->create();

        $this->assertCount(2, $user->assets);
        $this->assertTrue($user->assets->contains('symbol', AssetSymbol::BTC));
        $this->assertTrue($user->assets->contains('symbol', AssetSymbol::ETH));
    }

    public function test_asset_decimal_precision_is_preserved(): void
    {
        $asset = Asset::factory()
            ->withAmount('5.12345678')
            ->withLockedAmount('1.98765432')
            ->create();

        $this->assertEquals('5.12345678', $asset->amount);
        $this->assertEquals('1.98765432', $asset->locked_amount);
    }

    public function test_asset_has_correct_default_values(): void
    {
        $user = User::factory()->create();
        $asset = new Asset;
        $asset->user_id = $user->id;
        $asset->symbol = AssetSymbol::BTC;
        $asset->save();
        $asset->refresh();

        $this->assertEquals('0.00000000', $asset->amount);
        $this->assertEquals('0.00000000', $asset->locked_amount);
    }

    public function test_unique_constraint_prevents_duplicate_user_symbol(): void
    {
        $user = User::factory()->create();

        Asset::factory()->for($user)->symbol(AssetSymbol::BTC)->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        Asset::factory()->for($user)->symbol(AssetSymbol::BTC)->create();
    }

    public function test_different_users_can_have_same_symbol(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $asset1 = Asset::factory()->for($user1)->symbol(AssetSymbol::BTC)->create();
        $asset2 = Asset::factory()->for($user2)->symbol(AssetSymbol::BTC)->create();

        $this->assertNotEquals($asset1->id, $asset2->id);
        $this->assertEquals(AssetSymbol::BTC, $asset1->symbol);
        $this->assertEquals(AssetSymbol::BTC, $asset2->symbol);
    }

    public function test_assets_are_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $asset = Asset::factory()->for($user)->create();

        $assetId = $asset->id;

        $user->delete();

        $this->assertDatabaseMissing('assets', ['id' => $assetId]);
    }

    public function test_factory_symbol_method_creates_specific_symbol(): void
    {
        $btcAsset = Asset::factory()->symbol(AssetSymbol::BTC)->create();
        $ethAsset = Asset::factory()->symbol(AssetSymbol::ETH)->create();

        $this->assertEquals(AssetSymbol::BTC, $btcAsset->symbol);
        $this->assertEquals(AssetSymbol::ETH, $ethAsset->symbol);
    }

    public function test_factory_symbol_method_accepts_string(): void
    {
        $btcAsset = Asset::factory()->symbol('BTC')->create();
        $ethAsset = Asset::factory()->symbol('eth')->create();

        $this->assertEquals(AssetSymbol::BTC, $btcAsset->symbol);
        $this->assertEquals(AssetSymbol::ETH, $ethAsset->symbol);
    }

    public function test_symbol_is_cast_to_enum(): void
    {
        $asset = Asset::factory()->create();

        $this->assertInstanceOf(AssetSymbol::class, $asset->symbol);
    }

    public function test_factory_with_amount_sets_correct_amount(): void
    {
        $asset = Asset::factory()->withAmount('10.50000000')->create();

        $this->assertEquals('10.50000000', $asset->amount);
    }

    public function test_factory_with_locked_amount_sets_correct_locked_amount(): void
    {
        $asset = Asset::factory()->withLockedAmount('3.25000000')->create();

        $this->assertEquals('3.25000000', $asset->locked_amount);
    }
}
