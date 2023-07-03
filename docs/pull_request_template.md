Pull Request Checklist:

- [ ] Features and backlog bugs should be merged into the `Development` branch, **NOT** `master`
- [ ] Hotfixes should be merged into both the `Development` and `master` branches (at the same time).
- [ ] All new text is preferrably internationalized (i.e., no end-user-visible text is hard-coded on the PHP pages)
- [ ] There are no linter errors
- [ ] New features have responsive design (i.e., look aesthetically pleasing both full screen and with small or mobile screens)
- [ ] [Symbiota coding standards](https://docs.google.com/document/d/1-FwCZP5Zu4f-bPwsKeVVsZErytALOJyA2szjbfSUjmc/edit?usp=sharing) have been followed
- [ ] If any files have been reformatted (e.g., by an autoformatter), the reformat is its own, separate commit in the PR
- [ ] Comment which GitHub issue(s), if any does this PR address
- [ ] It is the code author's responsibility to merge their own pull request after it has been approved
- [ ] If this PR represents a merge into the `Development` branch, remember to use the **squash & merge** option
- [ ] If this PR represents a merge from the `Development` branch into the master branch, remember to use the **merge** option
- [ ] If the dev team has agreed that this PR represents the last PR going into the Development branch before a tagged release (i.e., before an imminent merge into the master branch), make sure to notify the team and [lock the `Development` branch](https://github.com/BioKIC/Symbiota/settings/branches) to prevent accidental merges while QA takes place

Thanks for contributing and keeping it clean!
