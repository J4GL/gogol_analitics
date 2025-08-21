<?php

class IPAuth {
    private $authorizedIPs;
    
    public function __construct() {
        $ipsString = $_ENV['AUTHORIZED_IPS'] ?? '127.0.0.1,::1';
        $this->authorizedIPs = array_map('trim', explode(',', $ipsString));
    }
    
    public function isAuthorized($ip = null) {
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        // Always allow loopback addresses
        if ($this->isLoopback($ip)) {
            return true;
        }
        
        foreach ($this->authorizedIPs as $authorizedIP) {
            if ($this->ipMatches($ip, $authorizedIP)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getClientIP() {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    private function isLoopback($ip) {
        // IPv4 loopback
        if ($ip === '127.0.0.1' || strpos($ip, '127.') === 0) {
            return true;
        }
        
        // IPv6 loopback
        if ($ip === '::1') {
            return true;
        }
        
        return false;
    }
    
    private function ipMatches($ip, $range) {
        // Single IP
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        // CIDR range
        return $this->ipInCidr($ip, $range);
    }
    
    private function ipInCidr($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr);
        
        // IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int)$mask);
            
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }
        
        // IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }
            
            $mask = (int)$mask;
            $byteMask = $mask >> 3;
            $bitMask = $mask & 7;
            
            // Compare full bytes
            if ($byteMask > 0 && substr($ipBin, 0, $byteMask) !== substr($subnetBin, 0, $byteMask)) {
                return false;
            }
            
            // Compare remaining bits
            if ($bitMask > 0 && $byteMask < 16) {
                $ipByte = ord($ipBin[$byteMask]);
                $subnetByte = ord($subnetBin[$byteMask]);
                $mask = 0xFF << (8 - $bitMask);
                
                return ($ipByte & $mask) === ($subnetByte & $mask);
            }
            
            return true;
        }
        
        return false;
    }
}
