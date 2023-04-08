<?php

namespace Niwee\Niwhiboutik;

class ByNiWee
{
    public function __construct()
    {
        $this->base_url = 'https://byniwee.io/wp-json/lmfwc/v2';
        $this->consumer_key = 'ck_6a6824ec6148717d29c31ca500e94af5213312b6';
        $this->consumer_secret = 'cs_f8fe8d6f97448966f3d46ade6105f867deae7765';
        $this->license_key = get_option('nwh_license_key');
    }

    public function get_license()
    {
        if ($this->test_connection())
        {
            if ($this->license_key !== '' && $this->license_key !== false)
            {
                if ($this->check_license($this->license_key))
                {
                    return $this->return_activation_state($this->license_key);
                }
                else
                {
                    update_option(
                        'nwh_license_activated',
                        'false'
                    );

                    return [
                        'status' => 'warning',
                        'message' => __(
                            'License key is invalid',
                            'niwhiboutik'
                        ),
                        'license' => $this->license_key,
                    ];
                }
            }
            else
            {
                update_option(
                    'nwh_license_activated',
                    'false'
                );

                return [
                    'status' => 'info',
                    'message' => __(
                        'License key is empty',
                        'niwhiboutik'
                    )
                ];
            }
        }
        else
        {
            update_option(
                'nwh_license_activated',
                'false'
            );

            return [
                'status' => 'error',
                'message' => __(
                    'Could not connect to the byniwee server.',
                    'niwhiboutik'
                ),
            ];
        }
    }

    public function test_connection()
    {
        $request = $this->get('');
        if (is_int($request) && $request !== 200)
        {
            return false;
        }
        else
        {
            if (is_object($request))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    /**
     * Get from the plugins API.
     */
    public function get(string $endpoint)
    {
        $response = wp_remote_get(
            $this->base_url . $endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret)
                ),
            )
        );

        if (is_wp_error($response))
        {
            error_log($response->get_error_message());

            return "{$response->get_error_code()}: {$response->get_error_message()}";
        }

        // error_log(print_r($response, true));
        if ($response['response']['code'] && $response['response']['code'] !== 200)
        {
            return $response['response']['code'];
        }
        else
        {
            return json_decode($response['body']);
        }
    }

    public function check_license($license)
    {
        if ($this->test_connection())
        {
            $request = $this->get('/licenses/' . $license);

            if (is_int($request) && $request !== 200)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    public function return_activation_state(string $license)
    {
        if ($this->is_license_activated($license) === false)
        {
            update_option(
                'nwh_license_activated',
                'false'
            );

            return [
                'status' => 'success',
                'message' => __(
                    'License key is valid, but requires activation.',
                    'niwhiboutik'
                ),
                'license' => $license,
                'activated' => false,
            ];
        }
        else
        {
            return [
                'status' => 'success',
                'message' => __(
                    'License key is valid and activated.',
                    'niwhiboutik'
                ),
                'license' => $license,
                'activated' => true,
            ];
        }
    }

    public function is_license_activated()
    {
        if ($this->test_connection())
        {
            $request = $this->get('/licenses/' . $this->license_key);

            if (!is_int($request))
            {
                // error_log(print_r($request, true));
                $response = $request->data;

                if ($response->timesActivated !== null)
                {
                    if ($response->timesActivated === $response->timesActivatedMax)
                    {
                        update_option(
                            'nwh_license_activated',
                            'true'
                        );

                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
    }

    public function update_license($request)
    {
        $data = $request->get_json_params();

        extract($data);

        if (isset($license))
        {
            update_option(
                'nwh_license_key',
                $license
            );
            if (!$this->check_license($license))
            {
                update_option(
                    'nwh_license_activated',
                    'false'
                );

                return [
                    'status' => 'error',
                    'message' => __(
                        'License key is invalid',
                        'niwhiboutik'
                    ),
                ];
            }
            else
            {
                return $this->return_activation_state($license);
            }
        }
        else
        {
            return [
                'status' => 'error',
                'message' => __(
                    'License key is empty',
                    'niwhiboutik'
                ),
            ];
        }
    }

    public function activations_left()
    {
        if ($this->test_connection())
        {
            $request = $this->get('/licenses/' . $this->license_key);

            if (is_int($request) && $request !== 200)
            {
                return [
                    'status' => 'error',
                    'message' => __(
                        'Could not connect to the byniwee server.',
                        'niwhiboutik'
                    ),
                ];
            }
            else
            {
                $response = $request->data;

                return [
                    'activations_left' => $response->timesActivatedMax - $response->timesActivated
                ];
            }
        }
        else
        {
            return [
                'status' => 'error',
                'message' => __(
                    'Could not connect to the byniwee server.',
                    'niwhiboutik'
                ),
            ];
        }
    }

    public function activate_license()
    {
        if ($this->test_connection())
        {
            if ($this->is_license_activated())
            {
                update_option(
                    'nwh_license_activated',
                    'true'
                );

                return [
                    'status' => 'success',
                    'message' => __(
                        'License key is already activated.',
                        'niwhiboutik'
                    ),
                ];
            }
            else
            {
                if ($this->check_license($this->license_key))
                {
                    $request = $this->get('/licenses/activate/' . $this->license_key);

                    if (is_int($request) && $request !== 200)
                    {
                        update_option(
                            'nwh_license_activated',
                            'false'
                        );

                        return [
                            'status' => 'error',
                            'activated' => false,
                            'message' => __(
                                'Could not activate license key.',
                                'niwhiboutik'
                            ),
                        ];
                    }
                    else
                    {
                        update_option(
                            'nwh_license_activated',
                            'true'
                        );

                        return [
                            'status' => 'success',
                            'activated' => true,
                            'message' => __(
                                'License key is activated.',
                                'niwhiboutik'
                            ),
                        ];
                    }
                }
                else
                {
                    update_option(
                        'nwh_license_activated',
                        'false'
                    );

                    return [
                        'status' => 'error',
                        'message' => __(
                            'License key is invalid',
                            'niwhiboutik'
                        ),
                    ];
                }
            }
        }
        else
        {
            update_option(
                'nwh_license_activated',
                'false'
            );

            return [
                'status' => 'error',
                'message' => __(
                    'Could not connect to the byniwee server.',
                    'niwhiboutik'
                ),
            ];
        }
    }

    public function is_license_activated_simple()
    {
        return get_option('nwh_license_activated') === 'true';
    }

    /**
     * Post to the plugins API.
     */
    public function post(string $endpoint)
    {
        $response = wp_remote_post(
            $this->base_url . $endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($this->consumer_key . ':' . $this->consumer_secret)
                ),
            )
        );

        if (is_wp_error($response))
        {
            error_log($response->get_error_message());

            return "{$response->get_error_code()}: {$response->get_error_message()}";
        }

        // error_log(print_r($response, true));
        if ($response['response']['code'] && $response['response']['code'] !== 200)
        {
            return $response['response']['code'];
        }
        else
        {
            return json_decode($response['body']);
        }
    }
}
