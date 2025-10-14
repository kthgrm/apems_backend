<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     * GET /api/audit-logs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AuditLog::with('user');

            // Filter by user if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by model type if provided
            if ($request->has('model_type')) {
                $query->where('model_type', $request->model_type);
            }

            // Filter by action if provided
            if ($request->has('action')) {
                $query->where('action', $request->action);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $auditLogs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $auditLogs,
                'message' => 'Audit logs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified audit log.
     * GET /api/audit-logs/{auditLog}
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        try {
            $auditLog->load('user');

            return response()->json([
                'success' => true,
                'data' => $auditLog,
                'message' => 'Audit log retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit logs for a specific model.
     * GET /api/audit-logs/model/{modelType}/{modelId}
     */
    public function getModelAuditLogs(Request $request, $modelType, $modelId): JsonResponse
    {
        try {
            $auditLogs = AuditLog::with('user')
                ->where('model_type', $modelType)
                ->where('model_id', $modelId)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $auditLogs,
                'message' => 'Model audit logs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve model audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get audit statistics.
     * GET /api/audit-logs/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_logs' => AuditLog::count(),
                'logs_today' => AuditLog::whereDate('created_at', today())->count(),
                'logs_this_week' => AuditLog::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'logs_this_month' => AuditLog::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'top_actions' => AuditLog::selectRaw('action, COUNT(*) as count')
                    ->groupBy('action')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
                'top_models' => AuditLog::selectRaw('model_type, COUNT(*) as count')
                    ->groupBy('model_type')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Audit statistics retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
