<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Cashier = 'cashier';
    case GasJockey = 'gas_jockey';

    /**
     * The permissions granted to members with this role.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Cashier => [
                Permission::Sales,
                Permission::CashFlow,
                Permission::SalesAnalysis,
                Permission::Customers,
            ],
            self::GasJockey => [
                Permission::Sales,
            ],
        };
    }

    public function allows(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
