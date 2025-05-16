<?php
function hasAnyReportAccess($permissions) {
    $reportMenus = ['allrole', 'admin', 'manager', 'accounting', 'logistic', 'sales', 'master_data_customer', 'master_data_supplier'];
    foreach ($reportMenus as $menu) {
        if (in_array($menu, $permissions)) return true;
    }
    return false;
}

// ✅ Permissions by role
$permissions = match ($role) {
    'Admin' => ['allrole', 'admin', 'manager', 'accounting', 'logistic', 'sales', 'master_data_customer', 'master_data_supplier'],
    'Manager' => ['allrole', 'manager', 'accounting', 'logistic', 'sales', 'master_data_customer', 'master_data_supplier'],
    'Accounting' => ['allrole', 'accounting', 'logistic', 'sales'],
    'Logistic' => ['allrole', 'logistic', 'master_data_supplier'],
    'Sales' => ['allrole', 'sales', 'master_data_customer'],
    default => []
};
?>