import React from 'react';
import { Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import Dashboard from './pages/Dashboard';
import Orders from './pages/Orders';
import Inventory from './pages/Inventory';
import InventoryItemDetail from './pages/InventoryItemDetail';
import StockTransfer from './pages/StockTransfer';
import StorageLocations from './pages/StorageLocations';
import Invoices from './pages/Invoices';
import Payments from './pages/Payments';
import EventLogs from './pages/EventLogs';
import Waterline from './pages/Waterline';

export default function App() {
  return (
    <Layout>
      <Routes>
        <Route path="/" element={<Dashboard />} />
        <Route path="/orders" element={<Orders />} />
        <Route path="/inventory" element={<Inventory />} />
        <Route path="/inventory/transfer" element={<StockTransfer />} />
        <Route path="/inventory/locations" element={<StorageLocations />} />
        <Route path="/inventory/:id" element={<InventoryItemDetail />} />
        <Route path="/invoices" element={<Invoices />} />
        <Route path="/payments" element={<Payments />} />
        <Route path="/event-logs" element={<EventLogs />} />
        <Route path="/waterline" element={<Waterline />} />
      </Routes>
    </Layout>
  );
}
