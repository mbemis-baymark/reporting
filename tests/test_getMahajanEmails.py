import subprocess
import os
import sys
import re

THIS_DIR = os.path.dirname(__file__)
SCRIPT = os.path.join(os.path.dirname(THIS_DIR), 'getMahajanEmails.php')

expected_emails = [
    'crothenbuhler@baymark.com',
    'employee1@company.com'
]

EMAIL_RE = re.compile(r'^[\w.-]+@[\w.-]+\.[A-Za-z]{2,}$')

def run_script():
    result = subprocess.run([
        'php', SCRIPT
    ], cwd=THIS_DIR, capture_output=True, text=True)
    return result

def main():
    res = run_script()
    if res.returncode != 0:
        print(res.stdout)
        print(res.stderr, file=sys.stderr)
        sys.exit(res.returncode)
    lines = [l.strip() for l in res.stdout.splitlines()]
    emails = [l for l in lines if EMAIL_RE.fullmatch(l)]
    if emails != expected_emails:
        print('Unexpected output:', emails)
        sys.exit(1)
    print('Mahajan script returned expected emails.')

if __name__ == '__main__':
    main()
