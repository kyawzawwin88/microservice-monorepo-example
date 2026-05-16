import React from 'react';
import LocationAddressFields from './LocationAddressFields';
import {
  fieldInputClass,
  type LocationFieldErrors,
  type LocationFormValues,
} from './locationFormUtils';

type Props = {
  mode: 'create' | 'edit';
  values: LocationFormValues;
  onChange: (values: LocationFormValues) => void;
  onSubmit: (e: React.FormEvent) => void;
  onCancel?: () => void;
  submitting: boolean;
  fieldErrors?: LocationFieldErrors;
};

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return <p className="mt-1 text-xs text-red-600">{message}</p>;
}

export default function StorageLocationForm({
  mode,
  values,
  onChange,
  onSubmit,
  onCancel,
  submitting,
  fieldErrors,
}: Props) {
  const idPrefix = mode;
  const setField = (patch: Partial<LocationFormValues>) => onChange({ ...values, ...patch });

  return (
    <form onSubmit={onSubmit} className="grid gap-3 sm:grid-cols-2">
      <div>
        <label htmlFor={`${idPrefix}-name`} className="block text-sm font-medium text-gray-700 mb-1">
          Name *
        </label>
        <input
          id={`${idPrefix}-name`}
          type="text"
          value={values.name}
          onChange={(e) => setField({ name: e.target.value })}
          className={fieldInputClass(!!fieldErrors?.name)}
          placeholder="Main Warehouse"
          aria-invalid={!!fieldErrors?.name}
        />
        <FieldError message={fieldErrors?.name} />
      </div>
      <div>
        <label htmlFor={`${idPrefix}-code`} className="block text-sm font-medium text-gray-700 mb-1">
          Code *
        </label>
        <input
          id={`${idPrefix}-code`}
          type="text"
          value={values.code}
          onChange={(e) => setField({ code: e.target.value.toUpperCase() })}
          className={fieldInputClass(!!fieldErrors?.code, 'w-full rounded-lg border px-3 py-2 text-sm font-mono')}
          placeholder="WH-MAIN"
          aria-invalid={!!fieldErrors?.code}
        />
        <FieldError message={fieldErrors?.code} />
      </div>
      <LocationAddressFields
        idPrefix={idPrefix}
        values={values.address}
        onChange={(address) => setField({ address })}
        fieldErrors={fieldErrors}
      />
      <div className="sm:col-span-2 flex flex-wrap gap-2">
        <button
          type="submit"
          disabled={submitting}
          className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
        >
          {submitting
            ? 'Saving…'
            : mode === 'create'
              ? 'Create location'
              : 'Save changes'}
        </button>
        {mode === 'edit' && onCancel && (
          <button
            type="button"
            onClick={onCancel}
            className="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
        )}
      </div>
    </form>
  );
}
