import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 shrink-0 items-center justify-center">
                <AppLogoIcon className="size-10" alt="" />
            </div>
            <div className="ml-2 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-semibold text-primary">
                    LinguaPath
                </span>
                <span className="truncate text-xs font-medium tracking-wide text-sidebar-foreground/70">
                    TOEFL Excellence
                </span>
            </div>
        </>
    );
}
