{{-- Update file resources/views/vendor/backpack/ui/inc/menu_items.blade.php --}}

{{-- Main Menu --}}
<x-backpack::menu-item title="Manajemen Saldo" icon="la la-wallet" :link="route('admin.wallet.index')" />

{{-- Data Management --}}
<x-backpack::menu-dropdown title="Data Management" icon="la la-database">
    <x-backpack::menu-dropdown-item title="Users" icon="la la-users" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Products" icon="la la-shopping-cart" :link="backpack_url('product')" />
    <x-backpack::menu-dropdown-item title="Product Commissions" icon="la la-percentage" :link="backpack_url('product-commission')" />
    <x-backpack::menu-dropdown-item title="Transactions" icon="la la-exchange" :link="backpack_url('transaction')" />
</x-backpack::menu-dropdown>

{{-- Content Management --}}
<x-backpack::menu-dropdown title="Content Management" icon="la la-file-alt">
    <x-backpack::menu-dropdown-item title="Banners" icon="la la-image" :link="backpack_url('banner')" />
    <x-backpack::menu-dropdown-item title="About Us" icon="la la-info-circle" :link="backpack_url('about-us')" />
</x-backpack::menu-dropdown>

{{-- System Management --}}
<x-backpack::menu-dropdown title="System" icon="la la-cogs">
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-user-tag" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>