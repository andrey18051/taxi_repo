<?php

namespace App\Rules;

use App\Http\Controllers\WebOrderController;
use App\Models\Combo;
use Illuminate\Contracts\Validation\Rule;

class ComboName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
         /**
         * Проверка адреса в базе
         */
        return Combo::where('name', $value)->first();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Адреси немає в базі";
    }
}
