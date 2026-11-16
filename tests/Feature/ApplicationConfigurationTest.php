<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    public function test_application_name_is_gearora(): void
    {
        $this->assertSame('GEARORA', config('app.name'));
    }

    public function test_application_timezone_is_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', date_default_timezone_get());
    }

    public function test_application_locale_is_indonesian(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('id', app()->getLocale());
    }

    public function test_debug_mode_is_controlled_by_environment(): void
    {
        $this->assertIsBool(config('app.debug'));
        $this->assertSame(
            filter_var(env('APP_DEBUG'), FILTER_VALIDATE_BOOL),
            config('app.debug'),
        );
    }
}
