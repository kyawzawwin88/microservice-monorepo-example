import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const nav = [
  { to: '/', label: 'Dashboard', icon: '📊' },
  { to: '/inventory', label: 'Inventory', icon: '📦' },
  { to: '/orders', label: 'Orders', icon: '🛒' },
  { to: '/invoices', label: 'Invoices', icon: '🧾' },
  { to: '/payments', label: 'Payments', icon: '💳' },
  { to: '/event-logs', label: 'Event Logs', icon: '📋' },
  { to: '/waterline', label: 'Waterline', icon: '🌊' },
];

export default function Layout({ children }: { children: React.ReactNode }) {
  const { pathname } = useLocation();

  return (
    <div className="flex h-screen">
      {/* Sidebar */}
      <aside className="w-64 bg-gray-900 text-white flex flex-col flex-shrink-0">
        <div className="px-6 py-5 border-b border-gray-700">
          <h1 className="text-lg font-bold tracking-tight">Microservice</h1>
          <p className="text-xs text-gray-400 mt-0.5">Monorepo Dashboard</p>
        </div>

        <nav className="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          {nav.map((item) => {
            const active = item.to === '/' ? pathname === '/' : pathname === item.to || pathname.startsWith(`${item.to}/`);
            return (
              <Link
                key={item.to}
                to={item.to}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
                  active
                    ? 'bg-indigo-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                }`}
              >
                <span className="text-base">{item.icon}</span>
                {item.label}
              </Link>
            );
          })}
        </nav>

        {/* Swagger links */}
        <div className="px-3 py-4 border-t border-gray-700">
          <p className="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
            API Docs (Swagger)
          </p>
          {[
            { label: 'Sales', port: 8001 },
            { label: 'Invoice', port: 8002 },
            { label: 'Payment', port: 8003 },
            { label: 'Inventory', port: 8004 },
          ].map((svc) => (
            <a
              key={svc.port}
              href={`http://localhost:${svc.port}/docs/api`}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-2 px-3 py-1.5 text-xs text-gray-400 hover:text-indigo-400 transition-colors"
            >
              <span>📄</span> {svc.label} API
            </a>
          ))}
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-y-auto bg-gray-50">
        <div className="max-w-7xl mx-auto px-6 py-8">{children}</div>
      </main>
    </div>
  );
}
