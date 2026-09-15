# Biometric Integration Guide

## Overview

CBT Pro supports web-based fingerprint biometric verification for student identity validation before exams. The system includes:

1. **Simulated Mode** - For development and testing without hardware
2. **SDK Integration Points** - Ready for real fingerprint hardware

## Supported Fingerprint SDKs

### 1. DigitalPersona U.are.U (Recommended)
- **Website:** https://www.hidglobal.com/products/reader-peripheral-modules/digitalpersona
- **SDK:** DigitalPersona Web SDK
- **Browser Support:** Chrome, Firefox (with plugin)

### 2. SecuGen Hamster
- **Website:** https://secugen.com/
- **SDK:** SecuGen FDx SDK Pro for Web
- **Browser Support:** Chrome, Edge

### 3. Mantra MFS100
- **Website:** https://www.mantratec.com/
- **SDK:** MFS100 Web API
- **Browser Support:** Chrome, Firefox

### 4. Futronic
- **Website:** http://www.futronic-tech.com/
- **SDK:** Futronic WebAPI

### 5. Startek FM220
- **Website:** https://startek-engineering.com/
- **SDK:** FM220 Web API

### 6. WebAuthn (Modern Browsers)
- Native browser API
- Limited fingerprint support (mainly Windows Hello)
- No additional plugins required

## Integration Steps

### Step 1: Install Browser Plugin
Each SDK provides a browser plugin/extension that must be installed on exam computers.

### Step 2: Configure SDK in Settings
Admin panel → Settings → Biometric Settings:
- Enable Biometric Verification
- Set Match Threshold (default: 90%)

### Step 3: Update JavaScript
Edit `assets/js/biometric.js` to use your SDK:

```javascript
// Example: DigitalPersona Integration
async function captureWithDigitalPersona() {
    try {
        const reader = new DP.Reader();
        const fingerprint = await reader.capture();
        return {
            template_data: JSON.stringify(fingerprint.template),
            template_hash: fingerprint.hash,
            quality_score: fingerprint.quality
        };
    } catch (error) {
        throw new Error('Capture failed: ' + error.message);
    }
}

// Example: Mantra MFS100 Integration
async function captureWithMantra() {
    return new Promise((resolve, reject) => {
        const mantra = new MFS100();
        mantra.capture((result) => {
            if (result.success) {
                resolve({
                    template_data: result.ANSI_TEMPLATE,
                    template_hash: result.hash,
                    quality_score: result.quality
                });
            } else {
                reject(new Error(result.error));
            }
        });
    });
}
```

### Step 4: Server-Side Verification
The `ajax/admin/verify_biometric.php` endpoint handles template matching:

```php
// Load stored template for student
$stored = $db->prepare("SELECT template_data FROM fingerprint_templates WHERE student_id = ?");
$stored->execute([$studentId]);
$template = $stored->fetch();

// Use SDK matching algorithm
$matchResult = $sdk->match($capturedTemplate, $template['template_data']);
$isMatch = $matchResult->score >= BIOMETRIC_MATCH_THRESHOLD;
```

## Simulated Mode (Development)

For testing without hardware:

1. Go to Admin → Biometric → Enrollment
2. Select "Simulated (Demo Mode)" from SDK dropdown
3. Click "Capture Fingerprint" - generates a simulated template
4. Click "Save Template" - stores in database

## Exam Day Workflow

### Student Login
1. Student logs in with matric number and password
2. If biometric is required, show verification modal
3. Student places finger on scanner
4. System matches against enrolled template
5. If match > threshold, allow exam access

### Admin Verification
1. Admin can verify students before exam in Biometric page
2. Real-time match score displayed
3. Manual override available for hardware issues

## Security Considerations

- Templates are encrypted at rest
- Matching happens server-side
- Quality score threshold prevents low-quality captures
- Max 3 attempts before requiring admin intervention

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Plugin not detected | Install browser plugin, restart browser |
| Poor quality captures | Clean scanner, adjust finger position |
| Match failures | Re-enroll student with better quality capture |
| Timeout errors | Check USB connection, restart scanner service |

## Custom SDK Integration

To integrate a new SDK:

1. Add SDK option to dropdown in `admin/biometric_enroll.php`
2. Add capture function in `assets/js/biometric.js`
3. Update verification endpoint if needed
4. Test thoroughly before production use
