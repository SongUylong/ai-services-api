<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    protected function created($data, $msg = null)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg ?? 'Create successfully',
        ], Response::HTTP_CREATED);
    }

    protected function updated($data = null, $msg = null)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg ?? 'Update successfully',
        ]);
    }

    protected function deleted($msg = null)
    {
        return response()->json([
            'message' => $msg ?? 'Delete successfully',
        ], Response::HTTP_OK);
    }

    protected function restored($msg = null)
    {
        return response()->json([
            'message' => $msg ?? 'Restore successfully',
        ], Response::HTTP_OK);
    }

    protected function forceDeleted($msg = null)
    {
        return response()->json([
            'message' => $msg ?? 'Force delete successfully',
        ], Response::HTTP_OK);
    }

    public static function okWithDataPagination($data = null, $msg = 'Successful request'): JsonResponse
    {
        $data = $data->response()->getData(true);
        return response()->json([
            'message' => $msg,
            'success' => true,
            'data' => $data['data'],
            'links' => $data['links'],
            'meta' => $data['meta'],
        ], Response::HTTP_OK);
    }

    protected function okWithData($data = null, $msg = null)
    {
        return response()->json([
            'data' => $data,
            'message' => $msg ?? 'Request successfully',
        ], Response::HTTP_OK);
    }

    public function okWithDataAndHeader($header, $data)
    {
        return $this->okWithData(array_merge(['headers' => $header], ['data' => $data]));
    }

    protected function okWithMsg($msg)
    {
        return response()->json([
            'message' => $msg,
        ], Response::HTTP_OK);
    }

    protected function msgWithCode($msg, $code)
    {
        return response()->json(['message' => $msg], $code);
    }

    protected function errors($errors)
    {
        return response()->json([
            'message' => 'The given data was invalid',
            'errors' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function noContent()
    {
        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    protected function file($fullPath, $name = null)
    {
        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'File Not Found!'], Response::HTTP_NOT_FOUND);
        }

        return response()->download($fullPath, $name ?? basename($fullPath));
    }
}
