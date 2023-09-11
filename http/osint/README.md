## Description

OSINT templates are available for conducting user enumeration across many websites. By utilizing the flag to input a user, Nuclei can verify the user's existence across all websites listed in the templates and report any instances of success.

## Usage

The templates within the `osint` directory are **self-contained** and do NOT require URLs as input because the OSINT templates have pre-defined static URLs. Each template in this directory expects the user(name), email, or phone number or list of it to be supplied as input using the `V`/`var` flag through the Nuclei engine.

```bash
# Running OSINT templates against a single user to test
nuclei -tags osint -var user=elon
```

**OSINT** templates can be also ran against list of usernames as well.

```console
$ cat user_names.txt

user_1
user_2
user_3
user_4
user_5
```

```bash
# Running OSINT templates against a list of users to test
nuclei -tags osint -var user=user_names.txt
```

### Categories

The OSINT templates are classified into categories such as `archived`, `art`, `blog`, `business`, `coding`, `dating`, `finance`, `gaming`, `health`, `hobby`, `images`, `misc`, `music`, `news`, `political`, `search`, `shopping`, `social`, `tech`, `video`, `porn`.

To execute OSINT templates within a particular category, you can apply a filter using the `tags` flag and set the prefix value to `osint-`.

```bash
# Running OSINT templates against the social category
nuclei -t osint/ -tags osint-social -var user=some-user

# Running OSINT templates against the multiple categories
nuclei -t osint/ -tags osint-social,osint-finance -var user=some-user
```

## Acknowledgment

These OSINT templates were inspired by the [WebBreacher/WhatsMyName](https://github.com/WebBreacher/WhatsMyName) repository.
