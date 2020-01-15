# Composer plugin to convert EOL

Simple composer plugin to convert EOL file to vendor directory.
Use to clean file before apply patch.

## Use

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "aamant/composer-eol-cleaner": "dev-master"
  },
  "extra": {
    "patches": {
      ...
    },
    "convert-eol": {
        "vendorName/PackageName": [
            "relative/filename"
        ]
    }
  }
}

```