# Nuclei Templates Community Rewards Program - FAQ

## What is the purpose of this rewards program?
The program is designed to reward the community for their efforts in contributing high-quality templates for critical and trending vulnerabilities.

## What are the bounty ranges for template submissions?
Bounties range from **$50 to $250**, depending on the complexity of the template and the effort required.

## Where can I find bounty issues?
Only issues listed by us on our GitHub repository with the 💎 **Bounty** label are eligible for rewards. You can find these bounty issues [here](https://github.com/projectdiscovery/nuclei-templates/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22%F0%9F%92%8E%20Bounty%22)

## What is the acceptance criteria for templates?
Templates must meet the following criteria:
1. **Complete POC**: A full Proof of Concept (POC) must be provided and not rely solely on version detection.
2. **Debug Data**: Include debug data to assist with template validation.
3. **Validation Required**: The template will be reviewed and validated before rewards are given.
4. **Accurate Matchers**: Use strong matchers to avoid false positives.
> **Note**: Triagers will make the final decision on whether a template qualifies for a reward based on validation and the acceptance criteria outlined.

## How do I start working on a bounty issue?
1. **Find an Issue**: Look for issues tagged with 💎 **Bounty**.
2. **Declare Work**: Comment with `/attempt #<issue_number>` to claim the issue.
3. **Submit Work**: Submit your pull request with `/claim #<issue_number>` in the PR description when ready.

## How often are new bounty issues added?
We add new bounty issues on a **weekly basis**, so make sure to check back regularly for fresh opportunities. In the future, you can expect many more bounty issues as the program expands, allowing more opportunities for contributors to participate and earn rewards.

## Can I collaborate with others?
Yes, you can collaborate with other contributors and split rewards by commenting:
```
/claim #<issue_number>
/split @contributor1
/split @contributor2
```

## Is there a limit to how many issues I can work on?
You can work on up to **3 issues** simultaneously.

## What happens if I don’t complete an issue on time?
Issues must be completed within **2 months**, or they will be closed.

## How are rewards distributed?
Rewards are distributed once the template is fully validated. If the issue remains unresolved for **few weeks**, the bounty may increase.

## What should I include in my template submission?
Include the following:
> Avoid adding code templates for CVEs that can be achieved using HTTP, TCP, or JavaScript. Such templates are blocked by default and won’t produce results, so we prioritize creating templates with other protocols unless exceptions are made.
- **Complete POC**: A working Proof of Concept.
- **Matchers**: Multiple matchers to prevent false positives.
- **Debug Data**: Data to assist the triage team in validation.
- **Metadata**: Include required fields like `id`, `name`, `author`, `severity`, `description`, and `reference`.

## What types of templates will be rejected?
Templates may be rejected if they:
- Rely solely on version detection.
- Lack a complete POC.
- Contain weak matchers or redundant changes to existing templates.

## What should I avoid when submitting a template?
- Avoid sharing real-world targets publicly.
- Don’t submit templates with weak matchers.
- Avoid unnecessary changes to existing templates.

## Is there a leaderboard for contributors?
Yes! We now have a **leaderboard** that showcases top contributors. You can check it out here: [Leaderboard](https://cloud.projectdiscovery.io/templates/leaderboard).

## Is this program permanent?
The rewards program is currently a test run, but we may make changes based on community feedback.

## What additional rewards are available besides bounties?
Beyond bounties, we also reward contributors with:
- **Swag** such as t-shirts and stickers.
- **Invites to security conferences** for standout contributors.
- **Stickers** as a token of appreciation for all first-time contributors, regardless of the bounty.

> Contributors who feel their pull request or issue was overlooked for first-time contributor stickers can ping us on our Discord for assistance: [ProjectDiscovery Discord](https://discord.com/invite/projectdiscovery).
