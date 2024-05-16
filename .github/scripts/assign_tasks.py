import requests
import sys
import json

# GitHub credentials
password = sys.argv[3]

repo_owner = "projectdiscovery"
repo_name = "nuclei-templates"
pr_user_list = ["DhiyaneshGeek", "pussycat0x", "ritikchaddha"]
issue_user_list = ["DhiyaneshGeek", "pussycat0x", "ritikchaddha", "princechaddha"]

headers = {'Authorization': f'Bearer {password}',
        'Accept': 'application/vnd.github+json',
        'X-GitHub-Api-Version': '2022-11-28'}

def get_issue_assignee(issue_number):
    issue_url = f"https://api.github.com/repos/{repo_owner}/{repo_name}/issues?per_page=2"
    response = requests.get(issue_url, headers=headers)

    if response.status_code == 200:
        issue_data = response.json()[1]
        assignee = issue_data["assignee"]["login"] if issue_data["assignee"] else "None"
        return assignee
    else:
        print(f"Failed to fetch assignee for issue #{issue_number}")
        return None

def assign_issue_or_pr(user, issue_number):
    url = f"https://api.github.com/repos/{repo_owner}/{repo_name}/issues/{issue_number}/assignees"
    data = { "assignees": [user] }
    response = requests.post(url, headers=headers, data=json.dumps(data))

    if response.status_code == 201:
        print(f"Assigned issue #{issue_number} to {user}")
    else:
        print(f"Failed to assign issue #{issue_number} to {user}. Status code: {response.status_code}")

def get_pr_assignee_and_reviewer(pull_request_number):
    pull_url = f'https://api.github.com/repos/{repo_owner}/{repo_name}/pulls?per_page=2'
    response = requests.get(pull_url, headers=headers)

    if response.status_code == 200:
        pull_request_data = response.json()[1]
        assignee = pull_request_data['assignee']['login'] if pull_request_data['assignee'] else None
        reviewers = [reviewer['login'] for reviewer in pull_request_data['requested_reviewers']]
        
        return assignee, reviewers
    else:
        print(f"Failed to retrieve pull request #{pull_request_number}. Response: {response.text}")
        return None, None

def get_pr_author(pull_request_number):
    pull_url = f'https://api.github.com/repos/{repo_owner}/{repo_name}/pulls/{pull_request_number}'
    response = requests.get(pull_url, headers=headers)

    if response.status_code == 200:
        pull_request_data = response.json()
        author = pull_request_data['user']['login']
        return author

    else:
        print(f"Failed to retrieve pull request #{pull_request_number}. Response: {response.text}")
        return None

def review_pr(user, pull_request_number):
    url = f'https://api.github.com/repos/{repo_owner}/{repo_name}/pulls/{pull_request_number}/requested_reviewers'
    data = { 'reviewers': [user] }
    response = requests.post(url, headers=headers, data=json.dumps(data))

    if response.status_code == 201:
        print(f"Review request for pull request #{pull_request_number} sent to {user} successfully.")
    else:
        print(f"Failed to send review request for pull request #{pull_request_number}. Response: {response.text}")
    
def main():
    if len(sys.argv) != 4:
        print("Usage: python assign_tasks.py <issue_number> <pr_or_issue> <token>")
        sys.exit(1)

    issue_number = int(sys.argv[1])
    type_ = sys.argv[2]
    if type_ == 'pr':
        assignee, reviewers = get_pr_assignee_and_reviewer(issue_number - 1)
        author = get_pr_author(issue_number)

        if reviewers:
            try:
                index = pr_user_list.index(reviewers[0])
                try: 
                    reviewer = pr_user_list[index + 1]
                except:
                    reviewer = pr_user_list[0]
                if reviewer == author:
                    reviewer = pr_user_list(pr_user_list.index(reviewer) + 1)
                    review_pr(reviewer, issue_number)
                else:
                    review_pr(reviewer, issue_number)

            except Exception as e:
                reviewer = pr_user_list[0]
                review_pr(reviewer, issue_number)
        else:
            for user in pr_user_list:
                if (user != author):
                    reviewer = user
                    review_pr(reviewer, issue_number)
                    break
        
        if assignee:
            try:
                index = pr_user_list.index(assignee)
                if (pr_user_list[index + 1] == reviewer):
                    assign_issue_or_pr(pr_user_list[index + 2], issue_number)
                else:
                    assign_issue_or_pr(pr_user_list[index + 1], issue_number)
            except Exception as e:
                if (pr_user_list[0] == reviewer):
                    assign_issue_or_pr(pr_user_list[1], issue_number)
                else:
                    assign_issue_or_pr(pr_user_list[0], issue_number)
        else:
            if (pr_user_list[0] == reviewer):
                assign_issue_or_pr(pr_user_list[1], issue_number)
            else:
                assign_issue_or_pr(pr_user_list[0], issue_number)
    elif type_ == 'issue':
        assignee = get_issue_assignee(issue_number-1)

        if assignee:
            try:
                index = issue_user_list.index(assignee)
                assign_issue_or_pr(issue_user_list[index + 1], issue_number)
            except Exception as e:
                assign_issue_or_pr(issue_user_list[0], issue_number)
        else:
            assign_issue_or_pr(issue_user_list[0], issue_number)

main()
