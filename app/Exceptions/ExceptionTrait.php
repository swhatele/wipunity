<?php
namespace App\Exceptions;

use App\Exceptions\TNCException;
use Exception;
// use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Request;
use Log;

trait ExceptionTrait
{
    /**
     * 
     * @param type $r
     * @param type $e
     * @return type
     * @throws Exception
     * @author Prem@Appit
     */
    public function apiException($r,$e)
    {
        switch ($e) {
            #Method not allowed.
            case ($e instanceof MethodNotAllowedHttpException):
                throw new Exception('Method not allowed.', Response::HTTP_METHOD_NOT_ALLOWED);
                break;
            # Invalid Endpoint.
            case ($e instanceof NotFoundHttpException):
                throw new Exception('Invalid end point.', Response::HTTP_NOT_FOUND);
                break;
            # model Endpoint.
            case ($e instanceof ModelNotFoundException):
                throw new Exception('Model not found.', Response::HTTP_MODEL);
                break;
            # Invalid Unexpected Value.
            case ($e instanceof UnexpectedValueException):
                throw new Exception('Unexpected value.', Response::HTTP_NOT_ACCEPTABLE);
                break;
            # Custom exception..
            case ($e instanceof TNCException):
                return $this->renderCustomException($e);
                break;

            # Invalid Parameters.
            case ($e instanceof QueryException):

                throw new Exception(array(
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ));
                break;
            default:
                return $this->renderException($e);
        }
    }

    /**
     * Return the Helping Habit Exception
     * Return all error response with 400 network status.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     * @author PremKumar
     */
    private function renderException($exception)
    {
        $code = $exception->getCode();

        if(empty($code) || $code == 0){
            # Custom exception with network status 200.
           return $this->renderCustomException($exception);
        }

        $error_info = json_decode($exception->getMessage());
        if (!is_null($error_info)) {
            $message = $error_info;
            $error = $error_info;
        } else {
            $message = ['message' => $exception->getMessage()];
            $error = [
                'route' => Request::capture()->getRequestUri(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }
        if ($exception->getCode() != 422) {
            Log::channel('daily')->debug(['info' => $error]);
        }
        # Define the response
        $response = [
            'status_code' => $exception->getCode(),
            'error' => $message,
        ];
        return response($response, 400);
    }

        //--------------------------------------------------------------------------
    /**
     * Project custom exception.
     * 
     * @param type $ex
     * @return type
     */
    public function renderCustomException($ex){
        $error = [
            'http_status' => $ex->getCode(),
            'route' => Request::capture()->getRequestUri(),
            'message' => $ex->getMessage(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine(),
        ];
        Log::channel('daily')->debug(['error' => $error]);
        $response['http_status'] = $ex->getCode();
        $response['message'] = $ex->getMessage();
        return response($response, 200);
    }
}
