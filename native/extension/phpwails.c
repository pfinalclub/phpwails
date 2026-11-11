#include <stddef.h>
#include <string.h>

/* Minimal extension boundary placeholder. The production build links this ABI
 * against the PHP headers and registers the native bridge functions. */
int phpwails_extension_api_version(void) { return 1; }
const char* phpwails_extension_name(void) { return "phpwails"; }
int phpwails_validate_payload(const char* payload, size_t length) { return payload != NULL && length > 0; }
