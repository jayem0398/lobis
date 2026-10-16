<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $defaults = [
        'company_name'              => 'LENDELL OUTSOURCING SOLUTIONS, INC.',
        'company_address'           => 'UNIT GF 10, CITYNET 1, 183 EDSA WACK-WACK GREENHILLS, MANDALUYONG CITY',
        'vat_tin'                   => '008-797-768-00000',
        'contact_email'             => 'billing@lendell.ph',
        'contact_phone'             => '(02) 8123-4567',
        'signatory_name'            => 'LOIDA CARINO',
        'signatory_title'           => 'Finance & Accounting Manager',
        'default_payment_terms'     => '30',
        'default_tax_type'          => 'vat',
        'default_tax_rate'          => '12',
        'default_withholding_tax'   => '2',
        'currency_symbol'           => '₱',
        'enable_email_notifications'=> '1',
        'require_deletion_approval' => '1',
    ];

    private function isAdmin($user): bool
    {
        if (!$user) return false;
        $role = strtolower(trim($user->role ?? 'user'));
        return $role === 'admin' || $role === 'superadmin';
    }

    public function index(Request $request)
    {
        if (!$this->isAdmin($request->user())) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $allSettings = Setting::all()->pluck('value', 'key')->toArray();
        $settings = array_merge($this->defaults, $allSettings);

        return response()->json([
            'status' => 'success',
            'data'   => $settings,
        ]);
    }

    public function update(Request $request)
    {
        if (!$this->isAdmin($request->user())) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }
        $validated = $request->validate([
            'company_name'              => 'required|string',
            'company_address'           => 'nullable|string',
            'vat_tin'                   => 'nullable|string',
            'contact_email'             => 'nullable|email',
            'contact_phone'             => 'nullable|string',
            'signatory_name'            => 'nullable|string',
            'signatory_title'           => 'nullable|string',
            'default_payment_terms'     => 'nullable|string',
            'default_tax_type'          => 'nullable|string',
            'default_tax_rate'          => 'nullable|numeric',
            'default_withholding_tax'   => 'nullable|numeric',
            'currency_symbol'           => 'nullable|string',
            'enable_email_notifications'=> 'nullable|boolean',
            'require_deletion_approval' => 'nullable|boolean',
        ]);

        foreach ($validated as $key => $val) {
            Setting::setByKey($key, is_bool($val) ? ($val ? '1' : '0') : (string)$val);
        }

        $allSettings = Setting::all()->pluck('value', 'key')->toArray();
        $settings = array_merge($this->defaults, $allSettings);

        return response()->json([
            'status'  => 'success',
            'message' => 'System & company settings updated successfully!',
            'data'    => $settings,
        ]);
    }
}
