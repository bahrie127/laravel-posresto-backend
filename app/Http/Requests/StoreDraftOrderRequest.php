<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDraftOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => 'nullable|integer',
            'total_item' => 'required|integer',
            'subtotal' => 'required|integer',
            'tax' => 'nullable|integer',
            'tax_percent' => 'nullable|integer',
            'discount' => 'nullable|integer',
            'discount_amount' => 'nullable|integer',
            'service_charge' => 'nullable|integer',
            'total' => 'required|integer',
            'transaction_time' => 'required|date',
            'table_number' => 'required|integer',
            'draft_name' => 'required|string',
            'room_id' => 'nullable|integer',
            'note' => 'nullable|string',
            'orders' => 'required|array',
            'orders.*.product_id' => 'required|integer',
            'orders.*.quantity' => 'required|integer',
            'orders.*.price' => 'required|integer',
            'orders.*.note' => 'nullable|string',
        ];
    }
}
