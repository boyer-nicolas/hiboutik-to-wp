<?php

namespace Niwee\Niwhiboutik;

class Notice
{
    /**
     * Update or create the stop notice
     */
    public static function stop(): array
    {
        date_default_timezone_set(get_option('timezone_string'));
        $message = __(
                'The last import from ',
                'niwhiboutik'
            ) . date('d/m/Y') . __(
                ' at ',
                'niwhiboutik'
            ) . date('H:i:s') . __(
                ' was stopped manually',
                'niwhiboutik'
            );

        if (!get_option('nwh_import_stop'))
        {
            add_option(
                'nwh_import_stop',
                $message
            );
        }
        else
        {
            update_option(
                'nwh_import_stop',
                $message
            );
        }

        $self = new self();
        $self::clearSuccess();
        $self::clearError();

        return [
            'success' => false,
            'message' => $message
        ];
    }

    /**
     * Clear success notice
     */
    public static function clearSuccess(): bool
    {
        return delete_option('nwh_import_success');
    }

    /**
     * Clear error notice
     */
    public static function clearError(): bool
    {
        return delete_option('nwh_import_error');
    }

    /**
     * Update or create the success notice
     */
    public static function success(string $executionTime): array
    {
        Api::write_import_status(
            __(
                'Import terminé avec succès',
                'niwhiboutik'
            )
        );
        date_default_timezone_set(get_option('timezone_string'));
        $message = __(
                'The last import from ',
                'niwhiboutik'
            ) . date('d/m/Y') . __(
                ' at ',
                'niwhiboutik'
            ) . date('H:i:s') . __(
                ' succedeed in ',
                'niwhiboutik'
            ) . $executionTime . ' minutes.';

        if (!get_option('nwh_import_success'))
        {
            add_option(
                'nwh_import_success',
                $message
            );
        }
        else
        {
            update_option(
                'nwh_import_success',
                $message
            );
        }

        $self = new self();

        $self::clearError();
        $self::clearStop();

        return [
            'success' => true,
            'message' => $message
        ];
    }

    /**
     * Clear stop notice
     */
    public static function clearStop(): bool
    {
        return delete_option('nwh_import_stop');
    }

    /**
     * Update or create the error notice
     */
    public static function error(string $executionTime): array
    {
        Api::write_import_status(
            __(
                'Import terminé avec des erreurs',
                'niwhiboutik'
            )
        );
        date_default_timezone_set(get_option('timezone_string'));
        $message = __(
                'The last import from ',
                'niwhiboutik'
            ) . date('d/m/Y') . __(
                ' at ',
                'niwhiboutik'
            ) . date('H:i:s') . __(
                ' failed in ',
                'niwhiboutik'
            ) . $executionTime . ' minutes.';

        if (!get_option('nwh_import_error'))
        {
            add_option(
                'nwh_import_error',
                $message
            );
        }
        else
        {
            update_option(
                'nwh_import_error',
                $message
            );
        }
        $self = new self();
        $self::clearSuccess();
        $self::clearStop();

        return [
            'success' => false,
            'message' => $message
        ];
    }

}