import React, { useEffect, useState } from 'react';
import StatusBadge from '../components/StatusBadge';
import { salesApi } from '../api/sales';
import { invoiceApi } from '../api/invoices';
import { paymentApi } from '../api/payments';
import { inventoryApi } from '../api/inventory';

interface ServiceStatus {
  name: string;
  port: number;
  status: string;
  timestamp: string | null;
  error: string | null;
}

const services = [
  { name: 'Sales Service', port: 8001, check: salesApi.health },
  { name: 'Invoice Service', port: 8002, check: invoiceApi.health },
  { name: 'Payment Service', port: 8003, check: paymentApi.health },
  { name: 'Inventory Service', port: 8004, check: inventoryApi.health },
];

/* ─── Shared diagram building blocks ─── */

const Box = ({
  label,
  sub,
  color = 'indigo',
  icon,
  small,
}: {
  label: string;
  sub?: string;
  color?: string;
  icon?: string;
  small?: boolean;
}) => {
  const colors: Record<string, string> = {
    indigo: 'bg-indigo-50 border-indigo-300 text-indigo-800',
    green: 'bg-green-50 border-green-300 text-green-800',
    amber: 'bg-amber-50 border-amber-300 text-amber-800',
    red: 'bg-red-50 border-red-300 text-red-800',
    blue: 'bg-blue-50 border-blue-300 text-blue-800',
    purple: 'bg-purple-50 border-purple-300 text-purple-800',
    gray: 'bg-gray-50 border-gray-300 text-gray-700',
    rose: 'bg-rose-50 border-rose-300 text-rose-800',
    cyan: 'bg-cyan-50 border-cyan-300 text-cyan-800',
    teal: 'bg-teal-50 border-teal-300 text-teal-800',
  };
  return (
    <div
      className={`border-2 rounded-lg ${small ? 'px-3 py-1.5' : 'px-4 py-2.5'} text-center ${colors[color] ?? colors.gray}`}
    >
      <div className={`font-semibold ${small ? 'text-xs' : 'text-sm'}`}>
        {icon && <span className="mr-1">{icon}</span>}
        {label}
      </div>
      {sub && <div className={`${small ? 'text-[10px]' : 'text-xs'} opacity-70 mt-0.5`}>{sub}</div>}
    </div>
  );
};

const Arrow = ({ label, down, color = 'gray' }: { label?: string; down?: boolean; color?: string }) => {
  const arrowColor: Record<string, string> = {
    gray: 'text-gray-400',
    indigo: 'text-indigo-400',
    green: 'text-green-400',
    red: 'text-red-400',
    amber: 'text-amber-400',
    blue: 'text-blue-400',
  };
  return (
    <div className={`flex ${down ? 'flex-col items-center py-1' : 'items-center px-1'} ${arrowColor[color] ?? arrowColor.gray}`}>
      {down ? (
        <>
          <svg className="w-5 h-5" fill="none" viewBox="0 0 20 20">
            <path d="M10 3v11m0 0l-3-3m3 3l3-3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
          {label && <span className="text-[10px] font-medium text-gray-500 mt-0.5 whitespace-nowrap">{label}</span>}
        </>
      ) : (
        <>
          {label && <span className="text-[10px] font-medium text-gray-500 mr-1 whitespace-nowrap">{label}</span>}
          <svg className="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 20 20">
            <path d="M3 10h11m0 0l-3-3m3 3l-3 3" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </>
      )}
    </div>
  );
};

const EventBubble = ({ label, color = 'blue' }: { label: string; color?: string }) => {
  const colors: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-700 border-blue-200',
    red: 'bg-red-100 text-red-700 border-red-200',
    green: 'bg-green-100 text-green-700 border-green-200',
    amber: 'bg-amber-100 text-amber-700 border-amber-200',
  };
  return (
    <span className={`inline-block border rounded-full px-3 py-1 text-[11px] font-mono font-medium ${colors[color] ?? colors.blue}`}>
      ⚡ {label}
    </span>
  );
};

/* ─── Workflow Diagrams ─── */

const NewOrderDiagram = () => (
  <div className="space-y-2">
    <p className="text-xs text-gray-500 mb-4">
      Creates an order, publishes event to Invoice → Payment → back to Sales for completion. Inventory reservation happens only after payment.
    </p>
    {/* Row: trigger */}
    <div className="flex flex-wrap items-center gap-2 justify-center">
      <Box label="UI" sub="POST /api/orders" color="gray" icon="🖥️" small />
      <Arrow label="" />
      <Box label="Sales Service" sub="OrderController::store" color="indigo" icon="🛒" />
    </div>
    <Arrow down label="starts workflow" color="indigo" />
    {/* Activities */}
    <div className="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-2">NewOrderWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="ValidateOrderActivity" sub="Idempotency check" color="indigo" small />
        <Arrow />
        <Box label="UpdateOrderStateActivity" sub="state=requested, status=submitted" color="indigo" small />
        <Arrow />
        <Box label="ClearOrderCacheActivity" color="indigo" small />
      </div>
      <Arrow down label="dispatch EventBusMessage" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="OrderCreated" />
      </div>
      <Arrow down label="to eventbus → invoice queue" color="blue" />
      <div className="flex justify-center">
        <Box label="UpdateOrderStateActivity" sub="state → completed" color="green" small />
      </div>
    </div>
    {/* Cross-service flow */}
    <Arrow down label="event consumed by" color="blue" />
    <div className="bg-amber-50/50 border border-amber-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-amber-600 uppercase tracking-wider mb-2">Invoice Service — CreateInvoiceWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="GenerateInvoiceActivity" sub="Idempotent create" color="amber" small />
        <Arrow />
        <Box label="UpdateInvoiceStateActivity" sub="state → completed" color="amber" small />
        <Arrow />
        <Box label="ClearInvoiceCacheActivity" color="amber" small />
      </div>
      <Arrow down label="dispatch" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="InvoiceCreated" />
      </div>
    </div>
    <Arrow down label="event consumed by" color="blue" />
    <div className="bg-green-50/50 border border-green-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-green-600 uppercase tracking-wider mb-2">Payment Service — ProcessPaymentWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="ChargePaymentActivity" sub="Idempotent charge" color="green" small />
        <Arrow />
        <Box label="UpdatePaymentStateActivity" sub="state → completed" color="green" small />
        <Arrow />
        <Box label="ClearPaymentCacheActivity" color="green" small />
      </div>
      <Arrow down label="dispatch" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="PaymentCompleted" color="green" />
      </div>
    </div>
    <Arrow down label="event consumed by Sales" color="blue" />
    <div className="bg-purple-50/50 border border-purple-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-purple-600 uppercase tracking-wider mb-2">Sales Service — CompleteOrderWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="UpdateOrderStatusActivity" sub="status → paid" color="purple" small />
        <Arrow />
        <Box label="ClearOrderCacheActivity" color="purple" small />
      </div>
      <Arrow down label="dispatch" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="OrderPaid" color="green" />
      </div>
    </div>
    <Arrow down label="event consumed by Inventory" color="blue" />
    <div className="bg-teal-50/50 border border-teal-100 rounded-xl p-3">
      <div className="text-xs font-bold text-teal-600 uppercase tracking-wider mb-1">Inventory Service — ReserveInventoryWorkflow</div>
      <div className="flex justify-center">
        <Box label="ReserveStockActivity" sub="Reserve inventory for order items" color="teal" small />
      </div>
    </div>
    {/* Failure path */}
    <div className="mt-4 border-t border-dashed border-red-200 pt-4">
      <p className="text-xs font-bold text-red-500 uppercase tracking-wider mb-2">⚠️ Failure Path (simulate_failure)</p>
      <div className="flex flex-wrap items-center gap-2 justify-center text-xs text-gray-500">
        <Box label="Invoice or Payment fails" sub="Random chance (50%)" color="red" small />
        <Arrow color="red" />
        <EventBubble label="InvoiceCreationFailed / PaymentProcessingFailed" color="red" />
        <Arrow color="red" />
        <Box label="Sales: order.state → failed" sub="Retry available in UI" color="red" small />
      </div>
    </div>
  </div>
);

const DeleteOrderDiagram = () => (
  <div className="space-y-2">
    <p className="text-xs text-gray-500 mb-4">
      Soft-deletes the order and publishes event to Inventory to release reserved stock.
    </p>
    <div className="flex flex-wrap items-center gap-2 justify-center">
      <Box label="UI" sub="DELETE /api/orders/:id" color="gray" icon="🖥️" small />
      <Arrow label="" />
      <Box label="Sales Service" sub="OrderController::destroy" color="indigo" icon="🛒" />
    </div>
    <Arrow down label="guard: no related invoice" color="indigo" />
    <Arrow down label="starts workflow" color="indigo" />
    <div className="bg-red-50/50 border border-red-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-red-600 uppercase tracking-wider mb-2">DeleteOrderWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="SoftDeleteOrderActivity" sub="Soft-delete order record" color="red" small />
        <Arrow />
        <Box label="ClearOrderCacheActivity" color="red" small />
      </div>
      <Arrow down label="dispatch EventBusMessage" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="OrderDeleted" color="red" />
      </div>
    </div>
    <Arrow down label="event consumed by Inventory" color="blue" />
    <div className="bg-teal-50/50 border border-teal-100 rounded-xl p-3">
      <div className="text-xs font-bold text-teal-600 uppercase tracking-wider mb-1">Inventory Service — ReleaseInventoryWorkflow</div>
      <div className="flex justify-center">
        <Box label="ReleaseStockActivity" sub="Release reserved stock back to available" color="teal" small />
      </div>
    </div>
  </div>
);

const DeliverOrderDiagram = () => (
  <div className="space-y-2">
    <p className="text-xs text-gray-500 mb-4">
      Manually triggered when order is shipped. Deducts reserved inventory (goods left warehouse).
    </p>
    <div className="flex flex-wrap items-center gap-2 justify-center">
      <Box label="UI" sub="PATCH /api/orders/:id/deliver" color="gray" icon="🖥️" small />
      <Arrow label="" />
      <Box label="Sales Service" sub="OrderController::deliver" color="indigo" icon="🛒" />
    </div>
    <Arrow down label="guard: status must be 'paid'" color="indigo" />
    <Arrow down label="starts workflow" color="indigo" />
    <div className="bg-green-50/50 border border-green-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-green-600 uppercase tracking-wider mb-2">DeliverOrderWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="UpdateOrderStatusActivity" sub="status: paid → delivered" color="green" small />
        <Arrow />
        <Box label="ClearOrderCacheActivity" color="green" small />
      </div>
      <Arrow down label="dispatch EventBusMessage" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="OrderDelivered" color="green" />
      </div>
    </div>
    <Arrow down label="event consumed by Inventory" color="blue" />
    <div className="bg-teal-50/50 border border-teal-100 rounded-xl p-3">
      <div className="text-xs font-bold text-teal-600 uppercase tracking-wider mb-1">Inventory Service — DeductInventoryWorkflow</div>
      <div className="flex justify-center">
        <Box label="DeductStockActivity" sub="Deduct reserved → reduce available (shipped)" color="teal" small />
      </div>
    </div>
  </div>
);

const CompleteOrderDiagram = () => (
  <div className="space-y-2">
    <p className="text-xs text-gray-500 mb-4">
      Automatically triggered when Payment service confirms payment. Updates order status to "paid" and reserves inventory.
    </p>
    <div className="flex flex-wrap items-center gap-2 justify-center">
      <Box label="Payment Service" sub="ProcessPaymentWorkflow" color="green" icon="💳" small />
      <Arrow label="dispatch" />
      <EventBubble label="PaymentCompleted" color="green" />
    </div>
    <Arrow down label="event consumed by Sales" color="blue" />
    <div className="flex justify-center">
      <Box label="Sales Service" sub="HandlePaymentCompleted listener" color="indigo" icon="🛒" />
    </div>
    <Arrow down label="starts workflow" color="indigo" />
    <div className="bg-purple-50/50 border border-purple-100 rounded-xl p-4 space-y-2">
      <div className="text-xs font-bold text-purple-600 uppercase tracking-wider mb-2">CompleteOrderWorkflow</div>
      <div className="flex flex-wrap items-center gap-2 justify-center">
        <Box label="UpdateOrderStatusActivity" sub="status: submitted → paid" color="purple" small />
        <Arrow />
        <Box label="ClearOrderCacheActivity" color="purple" small />
      </div>
      <Arrow down label="dispatch EventBusMessage" color="blue" />
      <div className="flex justify-center">
        <EventBubble label="OrderPaid" color="green" />
      </div>
    </div>
    <Arrow down label="event consumed by Inventory" color="blue" />
    <div className="bg-teal-50/50 border border-teal-100 rounded-xl p-3">
      <div className="text-xs font-bold text-teal-600 uppercase tracking-wider mb-1">Inventory Service — ReserveInventoryWorkflow</div>
      <div className="flex justify-center">
        <Box label="ReserveStockActivity" sub="Reserve stock for paid order items" color="teal" small />
      </div>
    </div>
  </div>
);

/* ─── C4 Level 1 Context Diagram ─── */

const C4ContextDiagram = () => (
  <div className="space-y-4">
    <p className="text-xs text-gray-500 mb-2">
      C4 Level 1 — System Context: shows how users interact with the system and how services communicate.
    </p>

    {/* User */}
    <div className="flex justify-center">
      <div className="border-2 border-gray-400 rounded-xl px-6 py-3 bg-gray-50 text-center">
        <div className="text-2xl mb-1">👤</div>
        <div className="text-sm font-bold text-gray-800">User</div>
        <div className="text-[10px] text-gray-500">[Person]</div>
        <div className="text-[10px] text-gray-400 mt-1">Manages inventory, orders,<br />invoices, and payments via UI</div>
      </div>
    </div>
    <Arrow down label="HTTPS / REST API" color="gray" />

    {/* UI */}
    <div className="flex justify-center">
      <div className="border-2 border-blue-400 rounded-xl px-6 py-3 bg-blue-50 text-center">
        <div className="text-2xl mb-1">🖥️</div>
        <div className="text-sm font-bold text-blue-800">React UI</div>
        <div className="text-[10px] text-blue-500">[Web Application]</div>
        <div className="text-[10px] text-blue-400 mt-1">Single-page application<br />React + Vite + Tailwind CSS</div>
      </div>
    </div>
    <Arrow down label="REST API via Nginx proxy" color="gray" />

    {/* Services grid */}
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      {/* Sales */}
      <div className="border-2 border-indigo-400 rounded-xl p-4 bg-indigo-50 text-center">
        <div className="text-2xl mb-1">🛒</div>
        <div className="text-sm font-bold text-indigo-800">Sales Service</div>
        <div className="text-[10px] text-indigo-500">[Laravel Microservice]</div>
        <div className="text-[10px] text-indigo-400 mt-2">
          Manages orders & workflows.<br />
          Orchestrates the full order lifecycle.
        </div>
        <div className="mt-2 text-[10px] text-indigo-500 font-mono">:8001</div>
      </div>
      {/* Invoice */}
      <div className="border-2 border-amber-400 rounded-xl p-4 bg-amber-50 text-center">
        <div className="text-2xl mb-1">🧾</div>
        <div className="text-sm font-bold text-amber-800">Invoice Service</div>
        <div className="text-[10px] text-amber-500">[Laravel Microservice]</div>
        <div className="text-[10px] text-amber-400 mt-2">
          Generates invoices from orders.<br />
          Listens to OrderCreated events.
        </div>
        <div className="mt-2 text-[10px] text-amber-500 font-mono">:8002</div>
      </div>
      {/* Payment */}
      <div className="border-2 border-green-400 rounded-xl p-4 bg-green-50 text-center">
        <div className="text-2xl mb-1">💳</div>
        <div className="text-sm font-bold text-green-800">Payment Service</div>
        <div className="text-[10px] text-green-500">[Laravel Microservice]</div>
        <div className="text-[10px] text-green-400 mt-2">
          Processes payments for invoices.<br />
          Listens to InvoiceCreated events.
        </div>
        <div className="mt-2 text-[10px] text-green-500 font-mono">:8003</div>
      </div>
      {/* Inventory */}
      <div className="border-2 border-teal-400 rounded-xl p-4 bg-teal-50 text-center">
        <div className="text-2xl mb-1">📦</div>
        <div className="text-sm font-bold text-teal-800">Inventory Service</div>
        <div className="text-[10px] text-teal-500">[Laravel Microservice]</div>
        <div className="text-[10px] text-teal-400 mt-2">
          Manages stock, reservations.<br />
          Listens to OrderPaid / Delivered / Deleted.
        </div>
        <div className="mt-2 text-[10px] text-teal-500 font-mono">:8004</div>
      </div>
    </div>

    {/* Event Bus */}
    <div className="flex justify-center">
      <Arrow down label="Async events via Database Queue" color="blue" />
    </div>
    <div className="flex justify-center">
      <div className="border-2 border-blue-500 border-dashed rounded-xl px-8 py-3 bg-blue-50/50 text-center max-w-md w-full">
        <div className="text-2xl mb-1">🔀</div>
        <div className="text-sm font-bold text-blue-800">Event Bus</div>
        <div className="text-[10px] text-blue-500">[Shared MySQL Database Queue]</div>
        <div className="text-[10px] text-blue-400 mt-1">
          Cross-service communication via EventBusMessage jobs.<br />
          Each service has dedicated queue workers.
        </div>
      </div>
    </div>

    {/* Infrastructure */}
    <div className="flex justify-center">
      <Arrow down label="Persistence & Caching" color="gray" />
    </div>
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg mx-auto">
      <div className="border-2 border-gray-400 border-dashed rounded-xl p-3 bg-gray-50 text-center">
        <div className="text-lg mb-1">🗄️</div>
        <div className="text-xs font-bold text-gray-700">MySQL Databases</div>
        <div className="text-[10px] text-gray-400 mt-1">
          5 databases: sales_db, invoice_db,<br />
          payment_db, inventory_db, eventbus_db
        </div>
      </div>
      <div className="border-2 border-red-300 border-dashed rounded-xl p-3 bg-red-50/50 text-center">
        <div className="text-lg mb-1">⚡</div>
        <div className="text-xs font-bold text-red-700">Redis</div>
        <div className="text-[10px] text-red-400 mt-1">
          Shared cache & session store<br />
          for all microservices
        </div>
      </div>
    </div>

    {/* Event flow summary */}
    <div className="mt-6 bg-gray-50 rounded-lg p-4 border border-gray-200">
      <h4 className="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Event Flow Between Services</h4>
      <div className="space-y-2 text-xs text-gray-600">
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">OrderCreated</span>
          <span>→</span>
          <span className="font-mono bg-amber-100 text-amber-700 px-2 py-0.5 rounded">Invoice</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">InvoiceCreated</span>
          <span>→</span>
          <span className="font-mono bg-green-100 text-green-700 px-2 py-0.5 rounded">Payment</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">PaymentCompleted</span>
          <span>→</span>
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">OrderPaid</span>
          <span>→</span>
          <span className="font-mono bg-teal-100 text-teal-700 px-2 py-0.5 rounded">Inventory</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">OrderDeleted</span>
          <span>→</span>
          <span className="font-mono bg-teal-100 text-teal-700 px-2 py-0.5 rounded">Inventory</span>
          <span className="text-gray-400 ml-1">(release reserved stock)</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span>→</span>
          <span className="font-mono bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[11px]">OrderDelivered</span>
          <span>→</span>
          <span className="font-mono bg-teal-100 text-teal-700 px-2 py-0.5 rounded">Inventory</span>
          <span className="text-gray-400 ml-1">(deduct reserved stock)</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-mono bg-amber-100 text-amber-700 px-2 py-0.5 rounded">Invoice</span>
          <span>→</span>
          <span className="font-mono bg-red-100 text-red-700 px-2 py-0.5 rounded text-[11px]">InvoiceCreationFailed</span>
          <span>→</span>
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span className="text-gray-400 ml-1">(order state → failed)</span>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          <span className="font-mono bg-green-100 text-green-700 px-2 py-0.5 rounded">Payment</span>
          <span>→</span>
          <span className="font-mono bg-red-100 text-red-700 px-2 py-0.5 rounded text-[11px]">PaymentProcessingFailed</span>
          <span>→</span>
          <span className="font-mono bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Sales</span>
          <span className="text-gray-400 ml-1">(order state → failed)</span>
        </div>
      </div>
    </div>
  </div>
);

/* ─── Workflow tabs ─── */

const workflowTabs = [
  { id: 'new', label: '🛒 New Order', component: NewOrderDiagram },
  { id: 'complete', label: '✅ Complete Order', component: CompleteOrderDiagram },
  { id: 'deliver', label: '📦 Deliver Order', component: DeliverOrderDiagram },
  { id: 'delete', label: '🗑️ Delete Order', component: DeleteOrderDiagram },
];

/* ─── Main Dashboard ─── */

export default function Dashboard() {
  const [statuses, setStatuses] = useState<ServiceStatus[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('new');

  const checkHealth = async () => {
    setLoading(true);
    const results = await Promise.all(
      services.map(async (svc) => {
        try {
          const res = await svc.check();
          return { name: svc.name, port: svc.port, status: res.status, timestamp: res.timestamp, error: null };
        } catch (err: unknown) {
          return {
            name: svc.name,
            port: svc.port,
            status: 'unhealthy',
            timestamp: null,
            error: err instanceof Error ? err.message : 'Unknown error',
          };
        }
      })
    );
    setStatuses(results);
    setLoading(false);
  };

  useEffect(() => {
    checkHealth();
    const interval = setInterval(checkHealth, 15000);
    return () => clearInterval(interval);
  }, []);

  const ActiveDiagram = workflowTabs.find((t) => t.id === activeTab)?.component ?? NewOrderDiagram;

  return (
    <div>
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
          <p className="mt-1 text-sm text-gray-500">
            Overview of all microservices health and architecture flow
          </p>
        </div>
        <button
          onClick={checkHealth}
          disabled={loading}
          className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition-colors"
        >
          {loading ? '⏳' : '🔄'} Refresh
        </button>
      </div>

      {/* Service Health Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-10">
        {statuses.map((svc) => (
          <div key={svc.name} className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center justify-between">
                <p className="text-sm font-medium text-gray-500">{svc.name}</p>
                <StatusBadge status={svc.status} />
              </div>
              <div className="mt-3">
                <p className="text-xs text-gray-400">Port: {svc.port}</p>
                {svc.timestamp && (
                  <p className="text-xs text-gray-400 mt-1">
                    Last check: {new Date(svc.timestamp).toLocaleTimeString()}
                  </p>
                )}
                {svc.error && <p className="text-xs text-red-500 mt-1">{svc.error}</p>}
              </div>
              <div className="mt-3">
                <a
                  href={`http://localhost:${svc.port}/docs/api`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-indigo-600 hover:text-indigo-500"
                >
                  View Swagger Docs →
                </a>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* ──────── C4 Level 1 Context Diagram ──────── */}
      <div className="bg-white shadow rounded-lg p-6 mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-1">C4 Level 1 — System Context Diagram</h2>
        <p className="text-xs text-gray-400 mb-4">High-level view of how users, services, and infrastructure interact</p>
        <C4ContextDiagram />
      </div>

      {/* ──────── DDD Workflow Diagrams ──────── */}
      <div className="bg-white shadow rounded-lg p-6 mb-8">
        <h2 className="text-lg font-semibold text-gray-900 mb-1">DDD Workflow Diagrams</h2>
        <p className="text-xs text-gray-400 mb-4">Detailed activity-level flow for each business workflow (durable-workflow engine)</p>

        {/* Tabs */}
        <div className="border-b border-gray-200 mb-6">
          <nav className="flex flex-wrap -mb-px gap-x-1">
            {workflowTabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`whitespace-nowrap px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${activeTab === tab.id
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Active diagram */}
        <ActiveDiagram />
      </div>

      {/* Quick Info */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white shadow rounded-lg p-5">
          <h3 className="text-sm font-semibold text-gray-900">🔄 Durable Workflows</h3>
          <p className="mt-2 text-xs text-gray-500">
            Each service uses <code className="bg-gray-100 px-1 rounded">laravel-workflow</code> for
            orchestrating activities with automatic retries and idempotency.
          </p>
        </div>
        <div className="bg-white shadow rounded-lg p-5">
          <h3 className="text-sm font-semibold text-gray-900">🎯 Model States (Spatie)</h3>
          <p className="mt-2 text-xs text-gray-500">
            Records transition through <code className="bg-gray-100 px-1 rounded">requested → completed | failed</code>{' '}
            using type-safe state machines.
          </p>
        </div>
        <div className="bg-white shadow rounded-lg p-5">
          <h3 className="text-sm font-semibold text-gray-900">🔗 Correlation ID</h3>
          <p className="mt-2 text-xs text-gray-500">
            A single UUID tracks one business process across all 4 services for full traceability.
          </p>
        </div>
      </div>
    </div>
  );
}
