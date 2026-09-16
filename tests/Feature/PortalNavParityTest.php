<?php

test('portal nav is the shared source of truth for desktop and mobile menus', function () {
    $portalNav = file_get_contents(resource_path('js/lib/portal-nav.ts'));
    $sidebar = file_get_contents(resource_path('js/components/template-sidebar.tsx'));
    $mobile = file_get_contents(resource_path('js/components/template-mobile-nav.tsx'));

    expect($portalNav)
        ->toContain("id: 'portal-pelaporan'")
        ->toContain("label: 'Portal Pelaporan'")
        ->toContain("href: '/portal-pelaporan'")
        ->toContain("id: 'inventaris'")
        ->toContain("label: 'Inventaris Portal'")
        ->toContain("id: 'inventaris-simrs'")
        ->toContain("label: 'Referensi SIMRS'")
        ->toContain("id: 'patroli-area'")
        ->toContain("label: 'Area & Ruang'")
        ->toContain('export function buildVisibleModuleGroups')
        ->toContain("href: '/aset'")
        ->not->toContain("id: 'patroli-titik'");

    expect($sidebar)
        ->toContain("from '@/lib/portal-nav'")
        ->toContain('buildVisibleModuleGroups')
        ->toContain('mainNavItems')
        ->toContain('settingsNavItems')
        ->not->toContain('const moduleGroups');

    expect($mobile)
        ->toContain("from '@/lib/portal-nav'")
        ->toContain('buildVisibleModuleGroups')
        ->toContain('mainNavItems')
        ->toContain('settingsNavItems')
        ->not->toContain('const moduleGroups')
        ->not->toContain("label: 'Titik & QR'")
        ->not->toContain("href: '/patroli/titik'");
});

test('portal nav hides payroll and patroli behind permission flags in source', function () {
    $portalNav = file_get_contents(resource_path('js/lib/portal-nav.ts'));

    expect($portalNav)
        ->toContain("group.id === 'payroll'")
        ->toContain('canAccessPayroll')
        ->toContain("group.id === 'patroli'")
        ->toContain('canAccessPatroli')
        ->toContain('buildSikatNavGroup')
        ->toContain('buildSimmutuNavGroup')
        ->toContain('buildTatanaskahNavGroup')
        ->toContain('buildWebOfficialNavGroup');
});
