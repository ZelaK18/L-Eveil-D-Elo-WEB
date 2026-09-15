<?php
// Jeton OAuth créé par bin/google-connect.php et stocké dans storage/.

class GoogleError extends RuntimeException {}

class GoogleNotConnected extends GoogleError {}

function http_request(string $method, string $url, ?array $json = null, ?array $form = null, ?string $bearer = null): array
{
    $headers = ['Accept: application/json'];
    $options = [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20];
    if ($bearer !== null) {
        $headers[] = "Authorization: Bearer $bearer";
    }
    if ($json !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif ($form !== null) {
        $options[CURLOPT_POSTFIELDS] = http_build_query($form);
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, $options + [CURLOPT_HTTPHEADER => $headers]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($response === false) {
        throw new GoogleError('Connexion à Google impossible : ' . curl_error($curl));
    }
    $data = json_decode($response, true) ?: [];
    if ($status >= 400) {
        $error = $data['error'] ?? null;
        $message = $data['error_description'] ?? $error['message'] ?? (is_string($error) ? $error : "HTTP $status");
        throw new GoogleError($message, $status);
    }
    return $data;
}

function google_token(): array
{
    return storage_read('google-token');
}

function google_can(string $scope): bool
{
    return in_array("https://www.googleapis.com/auth/$scope", google_token()['scopes'] ?? [], true);
}

function google_access_token(): string
{
    return with_lock('google-token', function (): string {
        $token = google_token();
        if (empty($token['refresh_token'])) {
            throw new GoogleNotConnected("Aucun compte Google n'est connecté : lancez php bin/google-connect.php.");
        }
        if (($token['expires_at'] ?? 0) > time()) {
            return $token['access_token'];
        }

        try {
            $response = http_request('POST', 'https://oauth2.googleapis.com/token', form: [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $token['refresh_token'],
                'client_id'     => secret('google.client_id'),
                'client_secret' => secret('google.client_secret'),
            ]);
        } catch (GoogleError $e) {
            if ($e->getCode() === 400) {
                throw new GoogleNotConnected("L'accès Google a expiré ou a été révoqué : lancez php bin/google-connect.php.");
            }
            throw $e;
        }

        $token['access_token'] = $response['access_token'];
        $token['expires_at'] = time() + (int) $response['expires_in'] - 60;
        storage_write('google-token', $token);
        return $token['access_token'];
    });
}

function google_api(string $method, string $url, ?array $json = null): array
{
    return http_request($method, $url, json: $json, bearer: google_access_token());
}

function calendar_url(string $suffix = ''): string
{
    return 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode(config('booking.calendar_id')) . '/events' . $suffix;
}

function calendar_events(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $tz = new DateTimeZone(config('booking.timezone'));
    $timestamp = fn(array $point): int => isset($point['dateTime'])
        ? strtotime($point['dateTime'])
        : (new DateTimeImmutable($point['date'], $tz))->getTimestamp();

    $events = [];
    $pageToken = null;
    do {
        $page = google_api('GET', calendar_url('?' . http_build_query(array_filter([
            'timeMin'      => $from->format(DATE_RFC3339),
            'timeMax'      => $to->format(DATE_RFC3339),
            'singleEvents' => 'true',
            'maxResults'   => 2500,
            'pageToken'    => $pageToken,
            'fields'       => 'nextPageToken,items(id,summary,start,end,status,transparency,attendees(self,responseStatus),extendedProperties(private))',
        ]))));

        foreach ($page['items'] ?? [] as $item) {
            $declined = array_filter($item['attendees'] ?? [], fn($a) => !empty($a['self']) && ($a['responseStatus'] ?? '') === 'declined');
            if (($item['status'] ?? '') === 'cancelled' || $declined) {
                continue;
            }
            $site = ($item['extendedProperties']['private']['source'] ?? '') === 'site';
            $events[] = [
                'id'      => $item['id'] ?? '',
                // Titre inutile pour un rendez-vous du site : le nom de la personne n'est pas gardé en cache.
                'summary' => $site ? '' : ($item['summary'] ?? ''),
                'start'   => $timestamp($item['start']),
                'end'     => $timestamp($item['end']),
                'free'    => ($item['transparency'] ?? '') === 'transparent',
                'site'    => $site,
            ];
        }
        $pageToken = $page['nextPageToken'] ?? null;
    } while ($pageToken);

    return $events;
}

function calendar_create_event(array $event): array
{
    return google_api('POST', calendar_url(), $event);
}

function calendar_update_event(string $id, array $fields): array
{
    return google_api('PATCH', calendar_url('/' . rawurlencode($id)), $fields);
}

function calendar_delete_event(string $id): void
{
    google_api('DELETE', calendar_url('/' . rawurlencode($id)));
}

function gmail_send(string $mime): void
{
    google_api('POST', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', ['raw' => base64url_encode($mime)]);
}
