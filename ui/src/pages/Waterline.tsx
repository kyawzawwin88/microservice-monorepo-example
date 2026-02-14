import React, { useState } from 'react';

const services = [
  { id: 'sales', label: '🛒 Sales Service', port: 8001 },
  { id: 'invoice', label: '🧾 Invoice Service', port: 8002 },
  { id: 'payment', label: '💳 Payment Service', port: 8003 },
  { id: 'inventory', label: '📦 Inventory Service', port: 8004 },
];

export default function Waterline() {
  const [activeService, setActiveService] = useState('sales');

  const activeSvc = services.find((s) => s.id === activeService) ?? services[0];
  const waterlineUrl = `http://localhost:${activeSvc.port}/waterline`;

  return (
    <div className="flex flex-col h-[calc(100vh-4rem)]">
      <div className="flex items-center justify-between mb-4 flex-shrink-0">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Workflow Monitor</h1>
          <p className="mt-1 text-sm text-gray-500">
            <a
              href="https://github.com/durable-workflow/waterline"
              target="_blank"
              rel="noopener noreferrer"
              className="text-indigo-600 hover:text-indigo-500 font-medium"
            >
              Waterline
            </a>{' '}
            — real-time workflow monitoring dashboard for each microservice
          </p>
        </div>
        <a
          href={waterlineUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          ↗ Open in New Tab
        </a>
      </div>

      {/* Service tabs */}
      <div className="border-b border-gray-200 mb-4 flex-shrink-0">
        <nav className="flex flex-wrap -mb-px gap-x-1">
          {services.map((svc) => (
            <button
              key={svc.id}
              onClick={() => setActiveService(svc.id)}
              className={`whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${
                activeService === svc.id
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              {svc.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Iframe container */}
      <div className="flex-1 bg-white shadow rounded-lg overflow-hidden border border-gray-200">
        <iframe
          key={activeService}
          src={waterlineUrl}
          title={`Waterline — ${activeSvc.label}`}
          className="w-full h-full border-0"
          style={{ minHeight: '500px' }}
        />
      </div>
    </div>
  );
}
