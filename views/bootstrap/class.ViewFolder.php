<?php
/**
 * Implementation of ViewFolder view
 *
 * @category   DMS
 * @package    LetoDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for ViewFolder view
 *
 * @category   DMS
 * @package    LetoDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class LetoDMS_View_ViewFolder extends LetoDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$orderby = $this->params['orderby'];
		$enableFolderTree = $this->params['enableFolderTree'];
		$showtree = $this->params['showtree'];
		$cachedir = $this->params['cachedir'];

		$folderid = $folder->getId();

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));

		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder), "view_folder", $folder);

		echo "<div class=\"row-fluid\">\n";
		echo "<div class=\"span3\">\n";
		if ($enableFolderTree) $this->printTreeNavigation($folderid,$showtree);
		echo "</div>\n";
		echo "<div class=\"span9\">\n";

		$this->contentHeading(getMLText("folder_infos"));

		$owner = $folder->getOwner();
		$this->contentContainerStart();
		echo "<table class=\"table-condensed\">\n";
		echo "<tr>";
		echo "<td>".getMLText("owner").":</td>\n";
		echo "<td><a href=\"mailto:".htmlspecialchars($owner->getEmail())."\">".htmlspecialchars($owner->getFullName())."</a></td>\n";
		echo "</tr>";
		echo "<tr>";
		echo "<td>".getMLText("comment").":</td>\n";
		echo "<td>".htmlspecialchars($folder->getComment())."</dtd\n";
		echo "</tr>";
		$attributes = $folder->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$attrdef = $attribute->getAttributeDefinition();
		?>
				<tr>
				<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
				<td><?php echo htmlspecialchars($attribute->getValue()); ?></td>
				</tr>
		<?php
			}
		}
		echo "</table>\n";
		$this->contentContainerEnd();

		$this->contentHeading(getMLText("folder_contents"));

		$subFolders = $folder->getSubFolders($orderby);
		$subFolders = LetoDMS_Core_DMS::filterAccess($subFolders, $user, M_READ);
		$documents = $folder->getDocuments($orderby);
		$documents = LetoDMS_Core_DMS::filterAccess($documents, $user, M_READ);

		if ((count($subFolders) > 0)||(count($documents) > 0)){
			print "<table class=\"table\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";	
			print "<th><a href=\"../out/out.ViewFolder.php?folderid=". $folderid .($orderby=="n"?"":"&orderby=n")."\">".getMLText("name")."</a></th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("status")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
//			print "<th>".getMLText("comment")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
		}
		else printMLText("empty_folder_list");


		foreach($subFolders as $subFolder) {

			$owner = $subFolder->getOwner();
			$comment = $subFolder->getComment();
			if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
			$subsub = $subFolder->getSubFolders();
			$subsub = LetoDMS_Core_DMS::filterAccess($subsub, $user, M_READ);
			$subdoc = $subFolder->getDocuments();
			$subdoc = LetoDMS_Core_DMS::filterAccess($subdoc, $user, M_READ);
			
			print "<tr class=\"folder\">";
		//	print "<td><img src=\"images/folder_closed.gif\" width=18 height=18 border=0></td>";
			print "<td><a href=\"out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\"><img src=\"".$this->imgpath."folder.png\" width=\"24\" height=\"24\" border=0></a></td>\n";
			print "<td><a href=\"out.ViewFolder.php?folderid=".$subFolder->getID()."&showtree=".$showtree."\">" . htmlspecialchars($subFolder->getName()) . "</a>";
			if($comment) {
				print "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
			}
			print "</td>\n";
			print "<td>".htmlspecialchars($owner->getFullName())."</td>";
			print "<td colspan=\"1\"><small>".count($subsub)." ".getMLText("folders")."<br />".count($subdoc)." ".getMLText("documents")."</small></td>";
			print "<td></td>";
//			print "<td>".htmlspecialchars($comment)."</td>";
			print "</tr>\n";
		}

		$previewer = new LetoDMS_Preview_Previewer($cachedir, 40);
		foreach($documents as $document) {

			$owner = $document->getOwner();
			$comment = $document->getComment();
			if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
			$docID = $document->getID();
			if($latestContent = $document->getLatestContent()) {
				$previewer->createPreview($latestContent);
				$version = $latestContent->getVersion();
				$status = $latestContent->getStatus();
				
				print "<tr>";

				if (file_exists($dms->contentDir . $latestContent->getPath())) {
					print "<td><a href=\"../op/op.Download.php?documentid=".$docID."&version=".$version."\">";
					if($previewer->hasPreview($latestContent)) {
						print "<img class=\"mimeicon\" width=\"40\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=40\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
					} else {
						print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
					}
					print "</a></td>";
				} else
					print "<td><img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\"></td>";
				
				print "<td><a href=\"out.ViewDocument.php?documentid=".$docID."&showtree=".$showtree."\">" . htmlspecialchars($document->getName()) . "</a>";
				if($comment) {
					print "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
				}
				print "</td>\n";
				print "<td>".htmlspecialchars($owner->getFullName())."</td>";
				print "<td>";
				if ( $document->isLocked() ) {
					print "<img src=\"".$this->getImgPath("lock.png")."\" title=\"". getMLText("locked_by").": ".htmlspecialchars($document->getLockingUser()->getFullName())."\"> ";
				}
				print getOverallStatusText($status["status"])."</td>";
				print "<td>".$version."</td>";
//				print "<td>".htmlspecialchars($comment)."</td>";
				print "</tr>\n";
			}
		}

		if ((count($subFolders) > 0)||(count($documents) > 0)) echo "</tbody>\n</table>\n";


		echo "</div>\n";

		$this->contentEnd();

		$this->htmlEndPage();
	} /* }}} */
}

?>
