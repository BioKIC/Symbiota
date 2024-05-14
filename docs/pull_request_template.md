# Pull Request Checklist:

# Pre-Approval

- [ ] There is a description section in the pull request that details what the proposed changes do. It can be very brief if need be, but it ought to exist.
- [ ] Hotfixes should be branched off of the `master` branch and **squash and merged** back into the `master` branch.
- [ ] Features and backlog bugs should be merged into the `Development` branch, **NOT** `master`
- [ ] All new text is preferably internationalized (i.e., no end-user-visible text is hard-coded on the PHP pages), and the [spreadsheet tracking internationalizations](https://docs.google.com/spreadsheets/d/133fps9w2pUCEjUA6IGCcQotk7dn9KvepMXJ2IWUZsE8/edit?usp=sharing) has been updated either with a new row or with checkmarks to existing rows.
- [ ] There are no linter errors
- [ ] New features have responsive design (i.e., look aesthetically pleasing both full screen and with small or mobile screens)
- [ ] [Symbiota coding standards](https://docs.google.com/document/d/1-FwCZP5Zu4f-bPwsKeVVsZErytALOJyA2szjbfSUjmc/edit?usp=sharing) have been followed
- [ ] If any files have been reformatted (e.g., by an autoformatter), the reformat is its own, separate commit in the PR
- [ ] Comment which GitHub issue(s), if any does this PR address
- [ ] If this PR makes any changes that would require additional configuration of any Symbiota portals outside of the files tracked in this repository, make sure that those changes are detailed in [this document](https://docs.google.com/document/d/1T7xbXEf2bjjm-PMrlXpUBa69aTMAIROPXVqJqa2ow_I/edit?usp=sharing).

# Post-Approval

- [ ] It is the code author's responsibility to merge their own pull request after it has been approved
- [ ] If this PR represents a merge into the `Development` branch, remember to use the **squash & merge** option
- [ ] If this PR represents a merge from the `Development` branch into the master branch, remember to use the **merge** option
- [ ] If this PR represents a hotfix into the `master` branch, a subsequent PR from `master` into `Development` should be made **merge** option (i.e., no squash).
- [ ] If the dev team has agreed that this PR represents the last PR going into the `Development` branch before a tagged release (i.e., before an imminent merge into the master branch), make sure to notify the team and [lock the `Development` branch](https://github.com/BioKIC/Symbiota/settings/branches) to prevent accidental merges while QA takes place. Follow the release protocol [here](https://github.com/BioKIC/Symbiota/blob/master/docs/release-protocol.md).
- [ ] Don't forget to delete your feature branch upon merge. Ignore this step as required.

Thanks for contributing and keeping it clean!
