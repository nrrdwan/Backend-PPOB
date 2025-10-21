{{-- This file is used for menu items by any Backpack v6 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

{{-- Main Menu --}}
<x-backpack::menu-item title="Manajemen Saldo" icon="la la-wallet" :link="route('admin.wallet.index')" />

{{-- Data Management --}}
<x-backpack::menu-dropdown title="Data Management" icon="la la-database">
    <x-backpack::menu-dropdown-item title="Users" icon="la la-users" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Products" icon="la la-shopping-cart" :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="Product Commissions" icon="la la-percentage" :link="backpack_url('product-commission')" />
    <x-backpack::menu-dropdown-item title="Transactions" icon="la la-exchange" :link="backpack_url('transaction')" />
</x-backpack::menu-dropdown>

{{-- System Management --}}
<x-backpack::menu-dropdown title="System" icon="la la-cogs">
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-user-tag" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>
<x-backpack::menu-item title="Banners" icon="la la-question" :link="backpack_url('banner')" />