# Implementation Plan: Waterline Workflow Monitor

**Date**: 2026-05-16 | **Status**: migrated

## Summary

Thin UI wrapper — iframe per microservice Waterline instance. ~75 LOC React.

## Technical Context

React 18, iframe to service-native Waterline routes. Each service registers `WaterlineServiceProvider`.

## Project Structure

```text
ui/src/pages/Waterline.tsx
{service}/app/Providers/WaterlineServiceProvider.php
```
