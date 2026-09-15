export type PortalAuthType = 'shared' | 'personal' | 'both';

export type CredentialType = 'use_shared' | 'personal';

export interface FormConfigSelectorField {
    selectors: string[];
}

export interface FormConfigExtraField {
    key: string;
    selectors: string[];
}

export interface FormConfig {
    is_spa: boolean;
    wait_timeout_ms: number;
    username_field: FormConfigSelectorField;
    password_field: FormConfigSelectorField;
    extra_fields: FormConfigExtraField[];
    auto_submit: boolean;
}

export interface Portal {
    id: number;
    name: string;
    slug: string;
    category: string;
    url: string;
    url_pattern: string | null;
    icon_path: string | null;
    icon_url?: string | null;
    description: string | null;
    auth_type: PortalAuthType;
    shared_username: string | null;
    has_shared_password?: boolean;
    shared_extra_fields: Record<string, unknown> | null;
    form_config: FormConfig | null;
    is_active: boolean;
    sort_order: number;
    user_credentials_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface UserPortalCredential {
    id: number;
    user_id: number;
    portal_id: number;
    credential_type: CredentialType;
    personal_username: string | null;
    personal_extra_fields: Record<string, unknown> | null;
    is_active: boolean;
    notes: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface PortalMappingSummary {
    id: number;
    name: string;
    category: string;
    auth_type: PortalAuthType;
    is_active: boolean;
    user_credentials_count: number;
}
