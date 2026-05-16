# Implementation Plan: Event Logs Viewer

**Date**: 2026-05-16 | **Status**: migrated

## Summary

UI tabbed viewer over four identical event-log APIs. Backend logging is per-service `EventLog` model.

## Project Structure

```text
ui/src/pages/EventLogs.tsx
{each-service}/app/Http/Controllers/EventLogController.php
{each-service}/app/Listeners/LogIncomingEvent.php
```
