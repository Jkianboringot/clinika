import { Link } from '@inertiajs/react';
import { LayoutDashboard, ShoppingCart, Map, Package, ClipboardList } from 'lucide-react';
import { PropsWithChildren } from 'react';

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Sales', href: '/sales', icon: ShoppingCart },
    { label: 'Branches', href: '/branches', icon: Map },
    { label: 'Products', href: '/products', icon: Package },
    { label: 'Inventories', href: '/inventories', icon: ClipboardList },
];

export default function AdminLayout({ title, children }: PropsWithChildren<{ title: string }>) {
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="flex min-h-screen bg-cream font-sans">
            <aside className="flex w-52 flex-shrink-0 flex-col gap-1 bg-gradient-to-b from-brand-orange to-brand-orange-hover px-3 py-4">
                <div className="mb-3 px-2 text-lg font-extrabold text-white">MB</div>
                <nav className="flex flex-1 flex-col gap-1">
                    {navItems.map(({ label, href, icon: Icon }) => {
                        const active = currentPath.startsWith(href);
                        return (
                            <Link
                                key={href}
                                href={href}
                                className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition ${
                                    active
                                        ? 'bg-white text-brand-orange shadow-card'
                                        : 'text-white/90 hover:bg-white/15'
                                }`}
                            >
                                <Icon size={18} />
                                {label}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            <div className="flex flex-1 flex-col">
                <header className="flex items-center justify-between border-b-2 border-brand-orange bg-white px-6 py-3 shadow-card">
                    <h1 className="text-lg font-bold text-ink">Admin Panel</h1>
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#3a2416] text-xs font-bold text-white">
                        AU
                    </div>
                </header>

                <main className="flex-1 p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-ink">{title}</h2>
                    </div>
                    {children}
                </main>
            </div>
        </div>
    );
}