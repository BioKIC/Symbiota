# Release protocol

## 1. Alert the team

A release PR should not happen while there are remaining work items in code review or QA.

Consult the team if there are still items in QA, or if other previous merges need to be omitted from the release.

The `Development` branch must be locked during the release procedure.
_Note: The `Development` branch should already be locked during QA (refer to the pull request template)._

## 2. Merge and create the PR

First, resolve any potential merge conflicts with master on the `Development` branch.

1. `git checkout master`
2. `git pull origin master`
3. `git checkout Development`
4. `git fetch origin Development`
5. `git merge master` (there should be no merge conflicts)
6. Update the version in `config/symbbase.php`.
7. Add the version change, commit, and push to `Development`.
8. Issue a pull request for merging the `Development` branch into the `master` branch.
9. Await approval, and then merge. _Note: do NOT use the "squash and merge" method; it will make subsequent merges with the `Development` branch more difficult. Instead, use the "Create a merge commit" option._

**In case of merge conflicts when merging Development into master, something went wrong. Investigate what happened thoroughly before continuing.**

## 3. Document and draft a new release

Coordinate the production of any necessary documentation with the team (this includes the `docs/CHANGELOG.md`).

After completion of the previous steps, draft a new release of the master branch on GitHub:

1. Navigate to [https://github.com/BioKIC/Symbiota/releases/new](https://github.com/BioKIC/Symbiota/releases/new).
2. Designate the target as the `master` branch.
3. Create a new tag following the [SemVer pattern](https://semver.org/) (vX.Y.Z). **This exact pattern (no period between v and the first name, for instance) must be followed explicitly.** The release title should follow the pattern, "Symbiota vX.Y.Z". You can leave the release description blank.
4. Publish the release.
5. Unlock the `Development` branch.
6. Notify the team about the release and that the `Development` branch has been unlocked.
