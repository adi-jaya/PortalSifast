export type * from './auth';
export type * from './navigation';
export type * from './ui';
export type * from './ticket';
export type * from './portal';

import type { Auth } from './auth';

export type SharedData = {
    name: string;
    auth: Auth;
    permissions: {
        can_manage_portals?: boolean;
        [key: string]: unknown;
    };
    sidebarOpen: boolean;
    [key: string]: unknown;
};
