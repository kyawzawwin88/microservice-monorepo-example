import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { inventoryApi, type StorageLocation } from '../api/inventory';
import StorageLocationForm from '../components/inventory/StorageLocationForm';
import {
  collectLocationFormErrors,
  emptyLocationFormValues,
  formatLocationAddressSummary,
  hasLocationFieldErrors,
  locationFieldErrorsFromApi,
  locationFormValuesFromLocation,
  locationPayloadFromForm,
  firstLocationFormErrorMessage,
  type LocationFieldErrors,
  type LocationFormValues,
} from '../components/inventory/locationFormUtils';
import { WAREHOUSE_LOCATION_PAGE_HEADING } from './storageLocationsPageCopy';

export default function StorageLocations() {
  const [locations, setLocations] = useState<StorageLocation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [showInactive, setShowInactive] = useState(false);

  const [editingId, setEditingId] = useState<number | null>(null);
  const [formValues, setFormValues] = useState<LocationFormValues>(emptyLocationFormValues());
  const [fieldErrors, setFieldErrors] = useState<LocationFieldErrors>({});
  const [saving, setSaving] = useState(false);
  const formSectionRef = useRef<HTMLElement>(null);

  const [deactivateTarget, setDeactivateTarget] = useState<StorageLocation | null>(null);
  const [deactivating, setDeactivating] = useState(false);

  const isEditing = editingId !== null;

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await inventoryApi.listAllLocations();
      setLocations(res.data);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to load locations');
    }
    setLoading(false);
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const visible = showInactive ? locations : locations.filter((l) => l.is_active);

  const resetForm = () => {
    setEditingId(null);
    setFormValues(emptyLocationFormValues());
    setFieldErrors({});
  };

  const handleFormChange = (values: LocationFormValues) => {
    setFormValues(values);
    setFieldErrors({});
    setError(null);
  };

  const startEdit = (loc: StorageLocation) => {
    setEditingId(loc.id);
    setFormValues(locationFormValuesFromLocation(loc));
    setFieldErrors({});
    setError(null);
    setSuccess(null);
    formSectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const clientErrors = collectLocationFormErrors(formValues);
    if (hasLocationFieldErrors(clientErrors)) {
      setFieldErrors(clientErrors);
      setError(firstLocationFormErrorMessage(clientErrors));
      return;
    }
    setSaving(true);
    setError(null);
    setFieldErrors({});
    setSuccess(null);
    const payload = locationPayloadFromForm(formValues);
    try {
      if (isEditing && editingId !== null) {
        await inventoryApi.updateLocation(editingId, payload);
        setSuccess('Location updated');
      } else {
        await inventoryApi.createLocation(payload);
        setSuccess('Location created');
      }
      resetForm();
      await load();
    } catch (err: unknown) {
      const apiFieldErrors = locationFieldErrorsFromApi(err);
      if (hasLocationFieldErrors(apiFieldErrors)) {
        setFieldErrors(apiFieldErrors);
      }
      setError(
        err instanceof Error
          ? err.message
          : isEditing
            ? 'Failed to update location'
            : 'Failed to create location'
      );
    }
    setSaving(false);
  };

  const confirmDeactivate = async () => {
    if (!deactivateTarget) return;
    setDeactivating(true);
    setError(null);
    setSuccess(null);
    try {
      await inventoryApi.deactivateLocation(deactivateTarget.id);
      if (editingId === deactivateTarget.id) {
        resetForm();
      }
      setDeactivateTarget(null);
      setSuccess(`"${deactivateTarget.name}" deactivated`);
      await load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to deactivate location');
    }
    setDeactivating(false);
  };

  return (
    <div className="p-6 max-w-5xl">
      <Link to="/inventory" className="text-indigo-600 hover:underline text-sm">
        ← Inventory
      </Link>
      <header className="mt-4 mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{WAREHOUSE_LOCATION_PAGE_HEADING}</h1>
          <p className="mt-1 text-sm text-gray-500">
            Manage warehouses and sites used for stock-in, transfers, and variation inventory.
          </p>
        </div>
        <label className="flex items-center gap-2 text-sm text-gray-600">
          <input
            type="checkbox"
            checked={showInactive}
            onChange={(e) => setShowInactive(e.target.checked)}
            className="rounded border-gray-300 text-indigo-600"
          />
          Show inactive
        </label>
      </header>

      {error && (
        <div className="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">{error}</div>
      )}
      {success && (
        <div className="bg-green-50 text-green-800 p-3 rounded-lg mb-4 text-sm">{success}</div>
      )}

      <section
        ref={formSectionRef}
        className="rounded-lg border border-gray-200 bg-white p-4 mb-6 shadow-sm"
      >
        <h2 className="text-lg font-semibold text-gray-900 mb-3">
          {isEditing ? 'Edit location' : 'Add location'}
        </h2>
        {isEditing && (
          <p className="mb-3 text-sm text-gray-500">
            Editing {formValues.name} ({formValues.code}). Changes apply when you save.
          </p>
        )}
        <StorageLocationForm
          mode={isEditing ? 'edit' : 'create'}
          values={formValues}
          onChange={handleFormChange}
          onSubmit={handleSubmit}
          onCancel={isEditing ? resetForm : undefined}
          submitting={saving}
          fieldErrors={fieldErrors}
        />
      </section>

      {loading ? (
        <p className="text-sm text-gray-500">Loading locations…</p>
      ) : visible.length === 0 ? (
        <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
          <p className="text-gray-700 font-medium">No storage locations yet</p>
          <p className="mt-2 text-sm text-gray-500">
            Create a location before recording stock movements or variation inventory.
          </p>
        </div>
      ) : (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <table className="min-w-full divide-y divide-gray-200 text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Name</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Code</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Address</th>
                <th className="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                <th className="px-4 py-3 text-right font-medium text-gray-600">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {visible.map((loc) => (
                <tr
                  key={loc.id}
                  className={
                    !loc.is_active
                      ? 'bg-gray-50'
                      : editingId === loc.id
                        ? 'bg-indigo-50/50'
                        : undefined
                  }
                >
                  <td className="px-4 py-3 font-medium text-gray-900">{loc.name}</td>
                  <td className="px-4 py-3 font-mono text-gray-600">{loc.code}</td>
                  <td className="px-4 py-3 text-gray-600 max-w-xs" title={loc.address_street ?? undefined}>
                    {formatLocationAddressSummary(loc)}
                  </td>
                  <td className="px-4 py-3">
                    {loc.is_active ? (
                      <span className="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                        Active
                      </span>
                    ) : (
                      <span className="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">
                        Inactive
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-right space-x-2">
                    <button
                      type="button"
                      onClick={() => startEdit(loc)}
                      className="text-indigo-600 hover:underline text-xs font-medium"
                    >
                      {editingId === loc.id ? 'Editing…' : 'Edit'}
                    </button>
                    {loc.is_active && (
                      <button
                        type="button"
                        onClick={() => setDeactivateTarget(loc)}
                        className="text-red-600 hover:underline text-xs font-medium"
                      >
                        Deactivate
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {deactivateTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" role="dialog" aria-modal="true">
            <h3 className="text-lg font-semibold text-gray-900">Deactivate location?</h3>
            <p className="mt-2 text-sm text-gray-600">
              &ldquo;{deactivateTarget.name}&rdquo; ({deactivateTarget.code}) will no longer appear in stock
              movement selectors. Historical data is preserved. Deactivation is blocked if stock remains at this site.
            </p>
            <div className="mt-6 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setDeactivateTarget(null)}
                className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={confirmDeactivate}
                disabled={deactivating}
                className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50"
              >
                {deactivating ? 'Deactivating…' : 'Deactivate'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
