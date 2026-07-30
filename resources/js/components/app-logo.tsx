import AppLogoIcon from './app-logo-icon';

const APP_NAME = 'Portal Sifast';
const APP_SUBTITLE = 'RS Aisyiyah Siti Fatimah';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary shadow-sm">
                <AppLogoIcon className="fill-current text-primary-foreground" />
            </div>

            <div className="ml-2.5 grid min-w-0 flex-1 text-left text-sm leading-tight">
                <span className="truncate text-sm font-semibold tracking-tight text-sidebar-foreground">
                    {APP_NAME}
                </span>
                <span className="truncate text-xs font-normal text-sidebar-muted">
                    {APP_SUBTITLE}
                </span>
            </div>
        </>
    );
}
