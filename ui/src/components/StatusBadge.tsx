import React from 'react';

const colorMap: Record<string, string> = {
  // Microservice event-processing states
  requested: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  failed: 'bg-red-100 text-red-800',

  // Business order statuses
  submitted: 'bg-blue-100 text-blue-800',
  paid: 'bg-purple-100 text-purple-800',
  delivered: 'bg-emerald-100 text-emerald-800',

  // Health
  healthy: 'bg-green-100 text-green-800',
  unhealthy: 'bg-red-100 text-red-800',
};

export default function StatusBadge({ status }: { status: string }) {
  const cls = colorMap[status?.toLowerCase()] ?? 'bg-gray-100 text-gray-800';
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${cls}`}>
      {status}
    </span>
  );
}
